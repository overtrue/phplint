# Cache Manager

This extension is in charge to speed-up execution of previous scan.

It provides three extra options for the `lint` command : 

- `cache-adapter` -- Change caching mechanism with [available adapters](https://symfony.com/doc/current/cache.html#available-cache-adapters) 

> [!TIP]
> If you specify `never` value, the PHPLint Cache class will use the `null` adapter of the the [Symfony Cache Component][symfony/cache].

- `cache-dir` -- Change default directory (`./.phplint.cache/`), if you used the [Filesystem Adapter][filesystem-adapter].
- `cache-ttl` -- Change the default lifetime (in seconds) for cache items, if you used the [Filesystem Adapter][filesystem-adapter].

[filesystem-adapter]: https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html
[symfony/cache]: https://github.com/symfony/cache
