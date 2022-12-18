<?php

/*
 * This file is part of the overtrue/phplint
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint;

/**
 * Class Cache.
 */
class Cache
{
    protected static string $filename = '.phplint-cache';

    /**
     * Fetch cache.
     *
     * @return mixed
     */
    public static function get()
    {
        $content = file_get_contents(self::getFilename());

        return $content ? json_decode($content, true) : null;
    }

    /**
     * Check cache exists.
     */
    public static function exists(): bool
    {
        return file_exists(self::getFilename());
    }

    /**
     * Alias if exists();.
     */
    public static function isCached(): bool
    {
        return self::exists();
    }

    /**
     * Set cache.
     *
     * @param mixed $contents
     *
     * @return int
     */
    public static function put($contents)
    {
        return file_put_contents(self::getFilename(), json_encode($contents));
    }

    /**
     * Set cache filename.
     */
    public static function setFilename(string $filename): void
    {
        self::$filename = $filename;
        self::makeFolderForFilename();
    }

    /**
     * Try to create the folder recursively where the cache file is stored.
     * It depends on current value of static::getFilename().
     */
    private static function makeFolderForFilename(): void
    {
        $filename = self::getFilename();
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
    }

    /**
     * Return cache filename.
     */
    public static function getFilename(): string
    {
        if (\is_dir(\dirname(self::$filename))) {
            return self::$filename;
        }

        return (getcwd() ?: './') . '/' . self::$filename;
    }
}
