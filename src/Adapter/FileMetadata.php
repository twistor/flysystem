<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;

class FileMetadata implements MetadataInterface
{
    private $size = 0;

    private $timestamp = 0;

    private $visibility = 'public';

    public function __construct(SplFileInfo $info)
    {
        $this->info = $info;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getType(): string
    {
        return 'file';
    }

    public function getVisibility(): string
    {
        $permissions = octdec(substr(decoct($this->info->getPerms()), -4));

        return $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
    }
}
