<?php

namespace League\Flysystem;

use League\Flysystem\Adapter\MetadataInterface;

interface FilesystemInterface
{
    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function hasDir(string $path, array $config = []): bool;
    public function hasFile(string $path, array $config = []): bool;

    /**
     * Read a file.
     *
     * @param string $path The path to the file.
     *
     * @throws FileNotFoundException
     *
     * @return string|false The file contents or false on failure.
     */
    public function read(string $path, array $config = []): string;

    /**
     * Retrieves a read-stream for a path.
     *
     * @param string $path The path to the file.
     *
     * @throws FileNotFoundException
     *
     * @return resource|false The path resource or false on failure.
     */
    public function readStream(string $path, array $config = []);

    /**
     * List contents of a directory.
     *
     * @param string $directory The directory to list.
     * @param bool   $recursive Whether to list recursively.
     *
     * @return array A list of file metadata.
     */
    public function listContents(string $directory = '', bool $recursive = false, array $config = []): array;

    /**
     * Get a file's metadata.
     *
     * @param string $path The path to the file.
     *
     * @throws FileNotFoundException
     *
     * @return array|false The file metadata or false on failure.
     */
    public function getMetadata(string $path, array $config = []): MetadataInterface;

    /**
     * Write a new file.
     *
     * @param string $path     The path of the new file.
     * @param string $contents The file contents.
     * @param array  $config   An optional configuration array.
     *
     * @throws FileExistsException
     *
     * @return bool True on success, false on failure.
     */
    public function write(string $path, string $contents, array $config = []): bool;

    /**
     * Write a new file using a stream.
     *
     * @param string   $path     The path of the new file.
     * @param resource $resource The file handle.
     * @param array    $config   An optional configuration array.
     *
     * @throws \InvalidArgumentException If $resource is not a file handle.
     * @throws FileExistsException
     *
     * @return bool True on success, false on failure.
     */
    public function writeStream(string $path, $resource, array $config = []): bool;

    /**
     * Update an existing file.
     *
     * @param string $path     The path of the existing file.
     * @param string $contents The file contents.
     * @param array  $config   An optional configuration array.
     *
     * @throws FileNotFoundException
     *
     * @return bool True on success, false on failure.
     */
    public function update(string $path, string $contents, array $config = []): bool;

    /**
     * Update an existing file using a stream.
     *
     * @param string   $path     The path of the existing file.
     * @param resource $resource The file handle.
     * @param array    $config   An optional configuration array.
     *
     * @throws \InvalidArgumentException If $resource is not a file handle.
     * @throws FileNotFoundException
     *
     * @return bool True on success, false on failure.
     */
    public function updateStream(string $path, $resource, array $config = []): bool;

    /**
     * Rename a file.
     *
     * @param string $path    Path to the existing file.
     * @param string $newpath The new path of the file.
     *
     * @throws FileExistsException   Thrown if $newpath exists.
     * @throws FileNotFoundException Thrown if $path does not exist.
     *
     * @return bool True on success, false on failure.
     */
    public function rename(string $path, string $newpath): bool;

    /**
     * Copy a file.
     *
     * @param string $path    Path to the existing file.
     * @param string $newpath The new path of the file.
     *
     * @throws FileExistsException   Thrown if $newpath exists.
     * @throws FileNotFoundException Thrown if $path does not exist.
     *
     * @return bool True on success, false on failure.
     */
    public function copy(string $path, string $newpath): bool;

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     *
     * @return bool True on success, false on failure.
     */
    public function deleteFile(string $path): bool;

    /**
     * Delete a directory.
     *
     * @param string $path
     *
     * @throws RootViolationException Thrown if $path is empty.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteDir(string $path): bool;

    /**
     * Create a directory.
     *
     * @param string $path The name of the new directory.
     * @param array  $config  An optional configuration array.
     *
     * @return bool True on success, false on failure.
     */
    public function createDir(string $path, array $config = []): bool;

    /**
     * Set the visibility for a file.
     *
     * @param string $path       The path to the file.
     * @param string $visibility One of 'public' or 'private'.
     *
     * @return bool True on success, false on failure.
     */
    public function setVisibility(string $path, string $visibility): bool;

    /**
     * Create a file or update if exists.
     *
     * @param string $path     The path to the file.
     * @param string $contents The file contents.
     * @param array  $config   An optional configuration array.
     *
     * @return bool True on success, false on failure.
     */
    public function put(string $path, string $contents, array $config = []): bool;

    /**
     * Create a file or update if exists.
     *
     * @param string   $path     The path to the file.
     * @param resource $resource The file handle.
     * @param array    $config   An optional configuration array.
     *
     * @throws \InvalidArgumentException Thrown if $resource is not a resource.
     *
     * @return bool True on success, false on failure.
     */
    public function putStream(string $path, $resource, array $config = []): bool;

    /**
     * Register a plugin.
     *
     * @param PluginInterface $plugin The plugin to register.
     *
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin): self;
}
