# Output Manager

This extension is in charge to handle properly the reporting results system.

That's includes all formats currently supported : 

- `console` (default)
- `checkstyle` (requires [`DOM` extension])
- `json` (requires [`Json` extension])
- `junit` (requires [`DOM` extension])
- `sarif` (depends on [bartlett/sarif-php-converters])

And provides two extra options for the `lint` command : 

- `output-file` -- Generate an output to the specified path (default: standard output) 
- `output-format` -- Support any formats previously mentioned

## Default frontend

When `PLINT_FRONTEND` is unset (or set to `cli`), PHPLint prints summary of your source code analysis.

> [!TIP]
> May be changed by setting the `PLINT_FRONTEND` environment variable.
>
> Special `noninteractive` keyword value is reserved for platform that don't want any results on standard output
> for the `console` format.
>
> This is equivalent to the `-n, --no-interaction` Symfony Console flag on `cli` frontend ([`SAPI`])
>
> You can also consider all others frontend (than `cli`) as non-interactive !

```shell
PLINT_FRONTEND=cli phplint lint /path/to/source/code
```

> [!CAUTION]
> The `output_manager` extension/plugin is not allowed/loaded by default on other environment than `dev`,
> except for the special `legacy` mode.
>
> You should consider to enable it explicitly.

```shell
PLINT_FRONTEND=cli PLINT_ALLOW_PLUGINS=output_manager phplint lint -x output_manager /path/to/source/code -e ci
PLINT_FRONTEND=cli PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager phplint lint /path/to/source/code --env prod
```

For example :

![Console output](../assets/output-console.png)

[bartlett/sarif-php-converters]: https://github.com/llaville/sarif-php-converters
[`DOM` extension]: https://www.php.net/manual/en/book.dom.php
[`Json` extension]: https://www.php.net/manual/en/book.json.php
[`SAPI`]: https://www.php.net/manual/en/function.php-sapi-name.php
