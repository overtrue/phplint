# Configuration

1. [Path][path]
1. [Exclude][exclude]
1. [Extensions][extensions]
1. [Show Warnings][warning]
1. [Jobs][jobs]
1. [Cache][cache]
1. [No caching][no-cache]
1. [Memory limit][memory-limit]
1. [JSON output][log-json]
1. [XML output][log-xml]
1. [Exit Code][no-files-exit-code]

The `phplint` command relies on a configuration file for loading settings. 
If a configuration file is not specified through the `--configuration|-c` option, following file will be used : `.phplint.yml`. 
If no configuration file is found, PHPLint will proceed with the default settings.

## Path (`path`)

The `path` (`string`|`string[]` default `.`) setting is used to specify where all directories and files to scan should resolve to.

If not specified, the base path used is the current working directory.

## Exclude (`exclude`)

The `exclude` (`string[]` default `[]`) setting is a list of directory paths relative to the base `path`.
All files listed inside these paths won't be scanned by PHPLint.

## Extensions (`extensions`)

The `extensions` (`string[]` default `[php]`) setting will check only files with selected extensions.

## Show Warnings (`warning`)

Use the `warning` (`bool` default `false`) setting (with `true`) if you want to show PHP Warnings too.
For example:

```php
<?php declare(encoding="utf8");
```
Script above generate `PHP Warning:  declare(encoding=...) ignored because Zend multibyte feature is turned off by settings in /.../tests/fixtures/encoding.php on line 1`
 
## Jobs (`jobs`)

The `jobs` (`int`|`string` default `5`) setting declare the maximum number of paralleled jobs to run (depend on your computer infrastructure).

## Cache (`cache`)

The `cache` (`string` default `.phplint.cache`) setting identify the local directory where to save lint results.
Default behavior will store results as regular files in a collection of directories on a locally mounted filesystem.

This setting is used only when the `cache-adapter` is set to `Filesystem` value (string is case-sensitive).

If you don't want to store results in a sub-folder of your working directory, please specify an absolute path. 
For example: `/tmp/my-phplint-cache`

NOTE: if you give an empty `cache` setting value, default directory used will be `/tmp/symfony-cache` (See [Symfony Cache component][symfony/cache])

[symfony/cache]: https://github.com/symfony/cache/

## No caching (`no-cache`)

Use the `no-cache` (`bool` default `false`) setting (with `true`) if you want to ignore previous scan results.

All files to analyse are lint again. That means checking may be slower than with an active cache.

## Memory limit (`memory-limit`)

Sometimes if a file to lint is too big for your current PHP ini `memory_limit` setting, 
you may override it temporally for your PHPLint processes.

The `memory-limit` (`init`|`string` default to your current PHP ini `memory_limit` setting) accept values defined by [following syntax][shorthand-byte-options].

Use `-1` if you want to have no memory limit.

[shorthand-byte-options]: https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes

## JSON output (`log-json`)

The `log-json` (`null`|`string` default `null` to print results to standard output) setting allow to write results in a JSON format.
For example:

```json
{
    "status": "failure",
    "errors": {
        "/absolute/path/to/tests/fixtures/syntax_error.php": {
            "absolute_file": "/absolute/path/to/tests/fixtures/syntax_error.php",
            "relative_file": "syntax_error.php",
            "error": "unexpected end of file in line 4",
            "line": 4
        }
    },
    "time_usage": "< 1 sec",
    "memory_usage": "8.0 MiB",
    "cache_usage": "0 hit, 1 miss",
    "files_count": 1,
    "options_used": {
        "quiet": false,
        "jobs": 10,
        "path": [
            "tests/fixtures/"
        ],
        "exclude": [
            "vendor"
        ],
        "extensions": [
            "php"
        ],
        "warning": true,
        "cache": ".phplint.cache",
        "no-cache": false,
        "configuration": ".phplint.yml",
        "memory-limit": "512M",
        "log-json": "php://stdout",
        "log-xml": false,
        "no-files-exit-code": false
    }
}
```

## XML output (`log-junit`)

The `log-junit` (`null`|`string` default `null` to print results to standard output) setting allow to write results in a JUnit XML format.
For example:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="PHP Linter" timestamp="2023-01-19T07:52:39+0000" time="&lt; 1 sec" tests="1" errors="1">
    <testcase errors="1" failures="0">
      <error type="Error" message="unexpected end of file in line 4">/absolute/path/to/tests/fixtures/syntax_error.php</error>
    </testcase>
  </testsuite>
</testsuites>
```

## Exit Code (`no-files-exit-code`) 

The `no-files-exit-code` (`bool` default `false`) setting allow to exit `phplint` command with failure (status code `1`) when no files processed.
By default, `phplint` exit with success (status code `0`)

[path]: #path-path
[exclude]: #exclude-exclude
[extensions]: #extensions-extensions
[warning]: #show-warnings-warning
[jobs]: #jobs-jobs
[cache]: #cache-cache
[no-cache]: #no-caching-no-cache
[memory-limit]: #memory-limit-memory-limit
[log-json]: #json-output-log-json
[log-xml]: #xml-output-log-xml
[no-files-exit-code]: #exit-code-no-files-exit-code