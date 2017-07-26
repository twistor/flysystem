<?php

namespace League\Flysystem\Adapter;

interface MetadataInterface
{
    public function getSize(): ?int;

    public function getTimestamp(): ?int;

    public function getType(): string;

    public function getVisibility(): ?string;
}
