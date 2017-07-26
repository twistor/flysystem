<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var string path prefix
     */
    protected $pathPrefix;

    /**
     * @var string
     */
    protected $pathSeparator = '/';

    /**
     * Set the path prefix.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setPathPrefix(string $prefix)
    {
        if ($prefix === '') {
            $this->pathPrefix = '';
            return;
        }

        $this->pathPrefix = rtrim($prefix, '\\/') . $this->pathSeparator;
    }

    /**
     * Get the path prefix.
     *
     * @return string path prefix
     */
    public function getPathPrefix(): string
    {
        return $this->pathPrefix;
    }

    /**
     * Prefix a path.
     *
     * @param string $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix(string $path): string
    {
        return $this->getPathPrefix() . ltrim($path, '\\/');
    }

    /**
     * Remove a path prefix.
     *
     * @param string $path
     *
     * @return string path without the prefix
     */
    public function removePathPrefix(string $path): string
    {
        return substr($path, strlen($this->getPathPrefix()));
    }
}
