<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\DirectoryMetadata;
use League\Flysystem\Adapter\MetadataInterface;
use League\Flysystem\Exception\RootViolationException;
use League\Flysystem\Path;
use League\Flysystem\Plugin\PluggableTrait;

/**
 * @method array getWithMetadata(string $path, array $metadata)
 * @method bool  forceCopy(string $path, string $newpath)
 * @method bool  forceRename(string $path, string $newpath)
 * @method array listFiles(string $path = '', boolean $recursive = false)
 * @method array listPaths(string $path = '', boolean $recursive = false)
 * @method array listWith(array $keys = [], $directory = '', $recursive = false)
 */
class Filesystem implements FilesystemInterface
{
    use PluggableTrait;
    use ConfigAwareTrait;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param Config|array     $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $this->adapter = $adapter;
        $this->setConfig($config);
    }

    /**
     * @inheritdoc
     */
    public function hasDir(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);

        if ($path === '') {
            return true;
        }

        $config = $this->prepareConfig($config);

        return $this->getAdapter()->hasDir($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function hasFile(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);

        if ($path === '') {
            return false;
        }

        $config = $this->prepareConfig($config);

        return $this->getAdapter()->hasFile($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->write($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $path, $resource, array $config = []): bool
    {
        if ( ! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects argument #2 to be a valid resource.');
        }

        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->writeStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function put(string $path, string $contents, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->put($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function putStream(string $path, $resource, array $config = []): bool
    {
        if ( ! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects argument #2 to be a valid resource.');
        }

        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->putStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function update(string $path, string $contents, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->update($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream(string $path, $resource, array $config = []): bool
    {
        if ( ! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects argument #2 to be a valid resource.');
        }

        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->updateStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path, array $config = [])
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        $object = $this->getAdapter()->read($path, $config);

        return $object['contents'] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path, array $config = [])
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        $object = $this->getAdapter()->readStream($path, $config);

        return $object['stream'] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newpath, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);

        if ($path === '') {
            throw new RootViolationException('The root directory can not be renamed.');
        }
        if ($newpath === '') {
            throw new RootViolationException('The root directory can not be overwritten.');
        }

        $config = $this->prepareConfig($config);

        return $this->getAdapter()->rename($path, $newpath, $config);
    }

    /**
     * @inheritdoc
     */
    public function copy(string $path, string $newpath, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);

        if ($path === '') {
            throw new RootViolationException('The root directory can not be copied.');
        }
        if ($newpath === '') {
            throw new RootViolationException('The root directory can not be overwritten.');
        }

        $config = $this->prepareConfig($config);

        return $this->getAdapter()->copy($path, $newpath, $config);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return $this->getAdapter()->deleteFile($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);

        if ($path === '') {
            throw new RootViolationException('The root directory can not be deleted.');
        }

        $config = $this->prepareConfig($config);

        return (bool) $this->getAdapter()->deleteDir($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return $this->getAdapter()->createDir($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function listContents(string $path = '', bool $recursive = false, array $config = []): array
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return $this->getAdapter()->listContents($path, $recursive, $config);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(string $path, array $config = []): MetadataInterface
    {
        $path = Util::normalizePath($path);

        if ($path === '') {
            return new DirectoryMetadata();
        }

        $config = $this->prepareConfig($config);

        return $this->getAdapter()->getMetadata($path, $config);
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility, array $config = []): bool
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        return $this->getAdapter()->setVisibility($path, $visibility, $config);
    }

    /**
     * Get the Adapter.
     *
     * @return AdapterInterface adapter
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }
}
