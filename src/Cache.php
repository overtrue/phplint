<?php

declare(strict_types=1);

namespace Overtrue\PHPLint;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\AdapterInterface;

use function get_debug_type;
use function md5_file;
use function str_replace;

/**
 * @since Release 7.0.0 (code-rewrites by Laurent Laville)
 */
final class Cache
{
    private AdapterInterface $cache;
    private LoggerInterface $logger;

    public function __construct(AdapterInterface $adapter, LoggerInterface $logger = null)
    {
        if (null === $logger) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;

        $this->cache = $adapter;
        if ($adapter instanceof LoggerAwareInterface) {
            $this->cache->setLogger($logger);
        }
    }

    public function isFresh(string $filename): bool
    {
        $currentFingerprint = md5_file($filename);

        $key = str_replace('/', '_', $filename);

        // Try to fetch item from cache
        $item = $this->cache->getItem($key);
        $fingerprintSaved = $item->get();

        $exists = $item->isHit();

        if ($currentFingerprint !== $fingerprintSaved && null !== $fingerprintSaved) {
            $message = "Cache item is not fresh.";
            $exists = false;    // cache must be revalidate
        } elseif ($exists) {
            $message = "Key file found in cache.";
        } else {
            $message = "No cache item found for key file.";
        }

        if ($exists) {
            $this->logger->notice($message, ['cache-adapter' => get_debug_type($this->cache), 'key' => $key, 'fingerprint' => $fingerprintSaved]);
        } else {
            $item->set($currentFingerprint);
            $this->cache->save($item);
            $this->logger->warning($message, ['cache-adapter' => get_debug_type($this->cache), 'key' => $key, 'fingerprint' => $currentFingerprint]);
        }

        return $exists;
    }
}
