# Migration Guide

If you're already a user of previous version of PHPLint, you probably want to use it in the same way.

## Begin by define the `mode`

- Define globally or locally the `PLINT_MODE` environment variable and set its value to `legacy`
- You can also define it on the `phplint` binary invocation.

For example:

```shell
PLINT_MODE=legacy phplint
```

> [!WARNING]
> 1. When Symfony Console `-v, --verbose` flag is provided, the current configuration settings is displayed on same format as 9.7 version.
> 2. Difference came from the profiling information printed between 9.7 and 9.8 versions

## Use or Not the `profile_manager`

With PHPLint 9.7, the profiler is always displayed. There is no capacity to disable it !

For example:

![legacy profiler](assets/profiler-legacy.png)

With PHPLint 9.8, we have now ability to enable or disable the profiler.

### Enabling the profiler

> [!WARNING]
> It's possible only if the `symfony/stopwatch` dependency is installed.

You have two possibilities : 

1. By using the special mode `profile` 
2. By using the `--profile` flag on command invocation

For example:

```shell
PLINT_MODE=legacy,profile phplint /path/to/source/code
PLINT_MODE=legacy phplint /path/to/source/code --profile
PLINT_MODE=legacy phplint /path/to/source/code --profile auto
```

By default, when available, the profiler is in `auto` mode, and print all information.

For example:

![standard profiler](assets/profiler-standard.png)

> [!TIP]
> Filtering the profiler information (time, memory, cache) is only available when you are not in `legacy` mode.
>
> For example:
> 
> ```shell
> PLINT_MODE=off,profile bin/phplint lint --profile time
> ```
> 

### Disabling the profiler

You have two possibilities :

1. By using the `--profile` flag on `phplint` command invocation, with `never` value
2. By setting explicitly, list of plugins allowed (`PLINT_ALLOWS_PLUGINS` environment variable) without the `profile_manager` extension

## Upgrade your configuration file

With PHPLint 9.7, the configuration file must be in YAML format. 
And the names should be either : `.phplint.yml.dist` or `.phplint.yml` when searching in current working directory,
unless you specify path explicitly with the `-c,--config` flag.

With PHPLint 9.8, the file candidates were enlarged to : `.phplint` and `.phplint.dist`, 
format/file extensions (`php`, `yaml`, `yml`, and `json`).

> [!CAUTION]
> Three directives were renamed to avoid conflicts :
>
> - `extensions` should be named `file-extensions`
> - `output` should be named `output-file`
> - `format` should be named `output-format`
>
> If you don't apply the new names, old one are automagically used to populate the new directives.
>
> If you still used them, you'll get deprecations displayed, on standard output and/or through the logger.
>

<!-- admonition separator -->

> [!IMPORTANT]
> If you use your own logger or never use it (`PLINT_LOGGER='Psr\Log\NullLogger'`), you won't be notified of any deprecations.
