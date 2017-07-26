<?php

declare(strict_types=1);

namespace League\Flysystem\Adapter;

use DirectoryIterator;
use FilesystemIterator;
use League\Flysystem\Adapter\LocalMetadata;
use League\Flysystem\Config;
use League\Flysystem\Exception\FileExistsException;
use League\Flysystem\Exception\FileNotFoundException;
use League\Flysystem\Exception\NotSupportedException;
use League\Flysystem\Exception\UnreadableFileException;
use League\Flysystem\Util;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Local extends AbstractAdapter
{
    /**
     * @var int
     */
    const SKIP_LINKS = 0001;

    /**
     * @var int
     */
    const DISALLOW_LINKS = 0002;

    /**
     * @var array
     */
    protected static $permissions = [
        'file' => [
            'public' => 0644,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ]
    ];

    /**
     * @var string
     */
    protected $pathSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var array
     */
    protected $permissionMap;

    /**
     * @var int
     */
    protected $writeFlags;
    /**
     * @var int
     */
    private $linkHandling;

    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param int    $linkHandling
     * @param array  $permissions
     *
     * @throws LogicException
     */
    public function __construct(string $root, int $writeFlags = LOCK_EX, int $linkHandling = self::DISALLOW_LINKS, array $permissions = [])
    {
        $this->permissionMap = array_replace_recursive(static::$permissions, $permissions);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;

        $resolved = is_link($root) ? realpath($root) : $root;
        $this->ensureDirectory($resolved);

        if ( ! is_readable($resolved)) {
            throw new LogicException('The root path ' . $root . ' is not readable.');
        }

        $this->setPathPrefix($resolved);
    }

    /**
     * Ensure the root directory exists.
     *
     * @param string $root root directory path
     *
     * @return void
     *
     * @throws Exception in case the root directory can not be created
     */
    protected function ensureDirectory(string $directory, int $permissions = 0)
    {
        $permissions = $permissions ?: $this->permissionMap['dir']['public'];

        if ( ! is_dir($directory)) {
            $umask = umask(0);
            @mkdir($directory, $permissions, true);
            umask($umask);

            if ( ! is_dir($directory)) {
                throw new \RuntimeException(sprintf('Impossible to create the directory "%s".', $directory));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function hasDir(string $path, Config $config): bool
    {
        return is_dir($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function hasFile(string $path, Config $config): bool
    {
        return is_file($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function put(string $path, string $contents, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        return $this->writeFileContents($location, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function putStream(string $path, $resource, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        return $this->writeStreamContents($location, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function update(string $path, string $contents, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        $this->assertFilePresent($location);

        return $this->writeFileContents($location, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream(string $path, $resource, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        $this->assertFilePresent($location);

        return $this->writeStreamContents($location, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        $this->assertFileAbsent($location);

        return $this->writeFileContents($location, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $path, $resource, Config $config): ?array
    {
        $location = $this->applyPathPrefix($path);

        $this->assertFileAbsent($location);

        return $this->writeStreamContents($location, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path, Config $config): array
    {
        $location = $this->applyPathPrefix($path);

        $contents = @file_get_contents($location);

        if ($contents === false) {
            $this->assertFilePresent($location);
            throw new UnreadableFileException($path);
        }

        return ['contents' => $contents];
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path, Config $config): array
    {
        $stream = @fopen($this->applyPathPrefix($path), 'rb');

        if ($string === false) {
            $this->assertFilePresent($location);
            throw new UnreadableFileException($path);
        }

        return ['stream' => $stream];
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newpath): bool
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        $this->assertFilePresent($location);
        $this->assertFileAbsent($destination);

        $perms = fileperms(dirname($location));

        $this->ensureDirectory(dirname($destination), $perms);

        return rename($location, $destination);
    }

    /**
     * @inheritdoc
     */
    public function copy(string $path, string$newpath): bool
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        $this->assertFilePresent($location);
        $this->assertFileAbsent($destination);

        $perms = fileperms(dirname($location));

        $this->ensureDirectory(dirname($destination), $perms);

        return copy($location, $destination);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $dirname): bool
    {
        $location = $this->applyPathPrefix($dirname);

        if ( ! is_dir($location)) {
            return false;
        }

        $contents = $this->getRecursiveDirectoryIterator($location, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            $this->guardAgainstUnreadableFileInfo($file);
            $this->deleteFileInfoObject($file);
        }

        return rmdir($location);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): bool
    {
        $location = $this->applyPathPrefix($path);

        return unlink($location);
    }

    /**
     * @inheritdoc
     */
    public function listContents(string $directory, bool $recursive, Config $config): array
    {
        $result = [];
        $location = $this->applyPathPrefix($directory);

        if ( ! is_dir($location)) {
            throw new FileNotFoundException($directory);
        }

        $iterator = $recursive ? $this->getRecursiveDirectoryIterator($location) : $this->getDirectoryIterator($location);

        foreach ($iterator as $file) {
            $result[] = $this->normalizeFileInfo($file);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(string $path, Config $config): MetadataInterface
    {
        $location = $this->applyPathPrefix($path);

        $this->assertFilePresent($location);

        return new LocalMetadata(new SplFileInfo($location));
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility): bool
    {
        $location = $this->applyPathPrefix($path);

        $type = is_dir($location) ? 'dir' : 'file';

        return chmod($location, $this->permissionMap[$type][$visibility]);
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $dirname, Config $config): bool
    {
        $location = $this->applyPathPrefix($dirname);

        if (file_exists($location)) {
            throw new FileExistsException($dirname);
        }

        $umask = umask(0);

        $visibility = $config->get('visibility', 'public');
        $success = mkdir($location, $this->permissionMap['dir'][$visibility], true);

        umask($umask);

        return $success;
    }

    /**
     * @param SplFileInfo $file
     */
    protected function deleteFileInfoObject(SplFileInfo $file)
    {
        switch ($file->getType()) {
            case 'dir':
                rmdir($file->getRealPath());
                break;
            case 'link':
                unlink($file->getPathname());
                break;
            default:
                unlink($file->getRealPath());
        }
    }

    /**
     * Normalize the file info.
     *
     * @param SplFileInfo $file
     *
     * @return array|void
     *
     * @throws NotSupportedException
     */
    protected function normalizeFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isLink()) {
            return $this->mapFileInfo($file);
        }

        if ($this->linkHandling & self::DISALLOW_LINKS) {
            throw NotSupportedException::forLink($file);
        }
    }

    /**
     * Get the normalized path from a SplFileInfo object.
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    protected function getFilePath(SplFileInfo $file)
    {
        $path = $this->removePathPrefix($file->getPathname());

        return trim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @param string $path
     * @param int    $mode
     *
     * @return RecursiveIteratorIterator
     */
    protected function getRecursiveDirectoryIterator(string $path, $mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * @param string $path
     *
     * @return DirectoryIterator
     */
    protected function getDirectoryIterator($path)
    {
        $iterator = new DirectoryIterator($path);

        return $iterator;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return array
     */
    protected function mapFileInfo(SplFileInfo $file)
    {
        $normalized = [
            'type' => $file->getType(),
            'path' => $this->getFilePath($file),
        ];

        $normalized['timestamp'] = $file->getMTime();

        if ($normalized['type'] === 'file') {
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
    }

    /**
     * @param SplFileInfo $file
     *
     * @throws UnreadableFileException
     */
    protected function guardAgainstUnreadableFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isReadable()) {
            throw UnreadableFileException::forFileInfo($file);
        }
    }

    protected function writeFileContents(string $location, string $contents, Config $config): ?array
    {
        $this->ensureDirectory(dirname($location));

        if (false === $size = file_put_contents($location, $contents, $this->writeFlags)) {
            return;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return ['size' => $size, 'visibility' => $visibility];
    }

    protected function writeStreamContents(string $location, $resource, Config $config): ?array
    {
        $this->ensureDirectory(dirname($location));
        $destination = fopen($location, 'w+b');

        if ( ! $destination) {
            return;
        }

        if (false === $size = stream_copy_to_stream($resource, $destination)) {
            return;
        }

        if ( ! fclose($destination)) {
            return;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return return ['size' => $size, 'visibility' => $visibility];
    }

    /**
     * Asserts a file is present.
     *
     * @param string $location path to file
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function assertFilePresent(string $location)
    {
        if ( ! is_file($location)) {
            throw new FileNotFoundException($this->removePathPrefix($location));
        }
    }

    /**
     * Asserts a file is absent.
     *
     * @param string $location path to file
     *
     * @throws FileExistsException
     *
     * @return void
     */
    public function assertFileAbsent(string $location)
    {
        if (is_file($location)) {
            throw new FileExistsException($this->removePathPrefix($location));
        }
    }
}
