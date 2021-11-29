<?php

namespace Overtrue\PHPLint;

use JetBrains\PhpStorm\Pure;

class Cache
{
    protected static string $filename = '.phplint-cache';

    public static function get(): mixed
    {
        $content = file_get_contents(self::getFilename());

        return $content ? json_decode($content, true) : null;
    }

    #[Pure]
    public static function exists(): bool
    {
        return file_exists(self::getFilename());
    }

    #[Pure]
    public static function isCached(): bool
    {
        return self::exists();
    }

    public static function put(mixed $contents): int
    {
        return file_put_contents(self::getFilename(), json_encode($contents));
    }

    public static function setFilename(string $filename): void
    {
        self::$filename = $filename;
        self::makeFolderForFilename();
    }

    private static function makeFolderForFilename(): void
    {
        $dir = dirname(self::getFilename());

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public static function getFilename(): string
    {
        if (\is_dir(\dirname(self::$filename))) {
            return self::$filename;
        }

        return (getcwd() ?: './') . '/' . self::$filename;
    }
}
