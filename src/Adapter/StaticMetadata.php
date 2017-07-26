<?php

namespace League\Flysystem\Adapter;

class StaticMetadata implements MetadataInterface
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
        return 'file';
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }
}
