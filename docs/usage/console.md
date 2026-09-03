# Console CLI

PHPLint 9.8 supports two console invocation modes controlled by the `PLINT_MODE`
environment variable.

## Default command mode

When `PLINT_MODE` is unset (or set to `off`), PHPLint behaves like a normal
multi-command Symfony Console application. Running `phplint` displays the
command list; invoke the linter explicitly:

```shell
phplint lint [<path>...]
phplint lint --help
```

## Legacy mode

Set `PLINT_MODE=legacy` to keep the single-command style where `lint` is the
default command:

```shell
PLINT_MODE=legacy phplint [<path>...]
PLINT_MODE=legacy phplint --help
```

The lint path defaults to the current working directory when omitted.

## Configuration

The default configuration filename remains `.phplint.yml`. Use the global
`--configuration|-c` option to select another file.

PHPLint 9.8 also supports configuration discovery modes:

- `--configuration auto` scans supported `.phplint` and `.phplint.dist`
  candidates (`php`, `yaml`, `yml`, and `json`).
- `--configuration always` uses the configured default candidate directly.
- `--configuration never` disables configuration loading.
- `--no-configuration` is retained for 9.7 compatibility but is deprecated in
  favor of `--configuration never`.

> [!CAUTION]
>
> Configuration file candidates in format PHP and JSON are reserved for future implementation. 
> Only YAML (yml) format is currently supported.

A basic YAML configuration can still look like:

```yaml
path: ./src
jobs: 10
file-extensions:
  - php
exclude:
  - vendor
warning: true
memory-limit: -1
no-cache: true
```

## Core lint options

PHPLint 9.8 separates source-file extensions from application extensions
(plugins):

- `--file-extensions` limits the source file extensions to lint.
- `-x|--extensions` is a global option for PHPLint extension managers/plugins.

The core `lint` command also exposes `--exclude`, `-j|--jobs`, `-w|--warning`,
`--memory-limit`, `--ignore-exit-code`, and `--dry-run`.

Additional cache, diagnostic, output, profile, and progress options are added by
the corresponding enabled extension managers. Because that option set is
extension-dependent, use `phplint lint --help` (default command mode) or
`PLINT_MODE=legacy phplint --help` (legacy mode) for the authoritative options
for the current environment.

For details on individual settings, see
[the configuration documentation](../configuration.md).
