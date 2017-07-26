<?php

namespace League\Flysystem\Exception;

class UnreadableFileException extends FileActionFailedException
{
    public static function forFileInfo(\SplFileInfo $fileInfo)
    {
        return new static(
            sprintf(
                'Unreadable file encountered: %s',
                $fileInfo->getRealPath()
            )
        );
    }
}
