<?php

namespace League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\MetadataInterface;
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
     * Get the Adapter.
     *
     * @return AdapterInterface adapter
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
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
        if ( ! ($object = $this->getAdapter()->read(new Path($path)))) {
            return false;
        }

        return $object['contents'];
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path, array $config = [])
    {
        $path = Util::normalizePath($path);

        if ( ! $object = $this->getAdapter()->readStream($path)) {
            return false;
        }

        return $object['stream'];
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

        return $this->getAdapter()->rename($path, $newpath);
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

        return $this->getAdapter()->copy($path, $newpath);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path, array $config = []): bool
    {
        $path = Util::normalizePath($path);

        return $this->getAdapter()->delete($path);
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

        return (bool) $this->getAdapter()->deleteDir($path);
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

        return $this->getAdapter()->listContents($path, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(string $path, array $config = []): MetadataInterface
    {
        $path = Util::normalizePath($path);

        return $this->getAdapter()->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility, array $config = []): bool
    {
        $path = Util::normalizePath($path);

        return $this->getAdapter()->setVisibility($path, $visibility);
    }
}
