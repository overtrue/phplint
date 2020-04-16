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
    /**
     * @var string
     */
    protected static $filename = '.phplint-cache';

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
     *
     * @return bool
     */
    public static function exists()
    {
        return file_exists(self::getFilename());
    }

    /**
     * Alias if exists();.
     *
     * @return bool
     */
    public static function isCached()
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
     *
     * @param string $filename
     */
    public static function setFilename($filename)
    {
        self::$filename = $filename;
        self::makeFolderForFilename();
    }

    /**
     * Try to create the folder recursively where the cache file is stored.
     * It depends on current value of static::getFilename().
     */
    private static function makeFolderForFilename()
    {
        $filename = self::getFilename();
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
    }

    /**
     * Return cache filename.
     *
     * @return string
     */
    public static function getFilename()
    {
        if (\is_dir(\dirname(self::$filename))) {
            return self::$filename;
        }

        return (getcwd() ?: './') . '/' . self::$filename;
    }
}
