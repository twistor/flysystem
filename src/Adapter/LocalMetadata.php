<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use SplFileInfo;

class LocalMetadata implements MetadataInterface
{
    private $info;

    public function __construct(SplFileInfo $info)
    {
        $this->info = $info;
    }

    public function getSize(): int
    {
        return $this->info->getSize();
    }

    public function getTimestamp(): int
    {
        return $this->info->getMTime();
    }

    public function getType(): string
    {
        return $this->info->getType();
    }

    public function getVisibility(): string
    {
        $permissions = octdec(substr(decoct($this->info->getPerms()), -4));

        return $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
    }
}
