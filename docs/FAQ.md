# FAQ

## I do not want to lose to my time with new PHPLint 9.8 features, what can I do to use the new version as a 9.7 version ?

- Define globally or locally the `PLINT_MODE` environment variable and set its value to `legacy`
- You can also define it on the `phplint` binary invocation.

For example:

```shell
PLINT_MODE=legacy phplint
```

> [!WARNING]
> 1. When Symfony Console `-v, --verbose` flag is provided, the current configuration settings is displayed on same format as 9.7 version.
> 2. Difference came from the profiling information printed between 9.7 and 9.8 versions

> [!CAUTION]
> If you use a YAML configuration file, three directives were renamed to avoid conflicts :
>
> - `extensions` should be named `file-extensions`
> - `output` should be named `output-file`
> - `format` should be named `output-format`

## I do not want to print the diagnostic results after each `lint` command invocation

- Define globally or locally the `PLINT_DIAGNOSTIC` environment variable and set its value to `never`
- You can also define it on the `phplint` binary invocation.

For example:

```shell
PLINT_DIAGNOSTIC=never phplint lint
```

## I do not want to print the profiling results after each `lint` command invocation

- Provide the `--profile=never` flag on the `phplint` binary invocation.

For example:

```shell
phplint lint --profile=never 
```

> [!WARNING]
> This option is only available if the `profile_manager` plugin/extension is allowed and loaded.
>
> This is only TRUE on `dev` environment (see `-e, --env` or `PLINT_ENV` setting)

> [!TIP]
> You can enable the `develop` mode (`PLINT_MODE` environment variable), to load all embedded extensions (that came with version 9.8)
> on any environment.

## I want to define a configuration file that is not one of the default candidate files allowed

- Define globally or locally the `PLINT_CONFIG` environment variable and set its value to the absolute file location.
- You can also define it on the `phplint` binary invocation.
- You can also define it on the `phplint` binary invocation with the standard `-c, --config` flag.

For example:

```shell
PLINT_CONFIG=/etc/.config/phplint/.phplint.yaml phplint lint 
XDG_CONFIG_HOME=/etc/xdg/.config/phplint phplint lint
```

PHPLint 9.8 implement a new configuration file discovery mode, by finding any of the candidate files 
on paths provided by the `XDG_CONFIG_DIRS` environment variable (Follows [XDG Base Directory Specification]).

## I want to use my own PSR-3 compatible logger

Default logger is an enhanced version of the Symfony Console Logger (`Symfony\Component\Console\Logger\ConsoleLogger`)

- Define globally or locally the `PLINT_LOGGER` environment variable and set its value to the FQCN logger class.
- You can also define it on the `phplint` binary invocation.

For example:

```shell
PLINT_LOGGER="Symfony\Component\Console\Logger\ConsoleLogger" phplint lint --verbose
PLINT_LOGGER="Psr\Log\NullLogger" phplint lint --verbose
```

## I want to use my own set of plugins (embedded or user)

You can define what plugins/extensions are : 

- allowed, by setting the `PLINT_ALLOWS_PLUGINS` environment variable accordingly to the list of their names
- loaded, by setting the `PLINT_DEFAULT_PLUGINS` environment variable accordingly to the list of their names
- loaded temporary, by defining each one (`-x,--extensions`) on the `phplint` binary invocation

> [!WARNING]
> If you want to use your own extension/plugin, this one must be loadable by your autoloader.
> You can use the bootstrapping feature (`-b, --bootstrap` flag), if your autoloader cannot handle it.

For example:

```shell
PLINT_ALLOWS_PLUGINS=output_manager,my_manager PLINT_DEFAULT_PLUGINS=output_manager,my_manager phplint lint
PLINT_ALLOWS_PLUGINS=output_manager,my_manager phplint -x my_manager --bootstrap bootstrap.php lint
```

[XDG Base Directory Specification]: https://specifications.freedesktop.org/basedir/latest/
