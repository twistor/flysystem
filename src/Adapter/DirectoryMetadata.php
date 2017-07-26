<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;

class DirectoryMetadata implements MetadataInterface
{
    private $size;
    private $timestamp;
    private $visibility;

    public function __construct(?int $size = null, ?int $timestamp = null, ?string $visibility = null)
    {
        $this->size = $size;
        $this->timestamp = $timestamp;
        $this->visibility = $visibility;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function getType(): string
    {
        return 'dir';
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }
}
