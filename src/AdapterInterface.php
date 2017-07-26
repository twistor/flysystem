<?php

namespace League\Flysystem;

use League\Flysystem\Adapter\MetadataInterface;

interface AdapterInterface
{
    /**
     * @const  VISIBILITY_PUBLIC  public visibility
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * @const  VISIBILITY_PRIVATE  private visibility
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function hasDir(string $path, Config $config): bool;

    public function hasFile(string $path, Config $config): bool;

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read(string $path, Config $config): array;

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream(string $path, Config $config): array;

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents(string $directory, bool $recursive, Config $config): array;

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata(string $path, Config $config): MetadataInterface;

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write(string $path, string $contents, Config $config): array;

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream(string $path, $resource, Config $config): array;

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update(string $path, string $contents, Config $config): array;

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream(string $path, $resource, Config $config): array;

    public function put(string $path, string $contents, Config $config): array;

    public function putStream(string $path, $resource, Config $config): array;

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename(string $path, string $newpath): bool;

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy(string $path, string $newpath): bool;

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function deleteFile(string $path): bool;

    /**
     * Delete a directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function deleteDir(string $path): bool;

    /**
     * Create a directory.
     *
     * @param string $path directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir(string $path, Config $config): bool;

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility(string $path, string $visibility): bool;
}
