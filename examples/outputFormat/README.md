# Output Format

Since version 9.4.0 PHPLint is able to define and support multiple output format more easily in a common way.

```text
  -o, --output=OUTPUT                      Generate an output to the specified path (default: standard output)
      --format=FORMAT                      Format of requested reports (multiple values allowed)
```

Since version 9.8.0 PHPLint flags were renamed (to avoid conflict with the `help` command options)

```text
  -o, --output-file=OUTPUT                 Generate an output to the specified path (default: standard output)
      --output-format=FORMAT               Format of requested reports (multiple values allowed)
```

All source code are available in [examples outputFormat directory][examples-format-folder]

## Dump Linter output 

The `DumpOutput` class will print your linter results as valid PHP code representation by PHP function [var_export][var-export].

```shell
php examples/outputFormat/sarif.php --bootstrap examples/outputFormat/bootstrap.php --output-class DumpOutput
```


## SARIF Format

The Static Analysis Results Interchange Format ([SARIF][sarifweb]) is supported optionally by PHPLint.

The `Overtrue\PHPLint\Output\SarifOutput` class with help of `bartlett/sarif-php-converters` 
will print a standard SARIF 2.1.0 JSON format that may be customized. 
See package [repository][sarif-php-converters] documentation for more explains.

You'll need to install the package as any other dependency with following command :

```shell
composer require --dev bartlett/sarif-php-converters
```

### Example 1

```shell
php examples/outputFormat/sarif.php --bootstrap examples/outputFormat/bootstrap.php
```

This example use the default `SarifOutput` class.

### Example 2

While example 1 used the default `PhpLintConverter` of `bartlett/sarif-php-converters` package, you can use your own version.

E.g: with `MyPhpLintConverter`

```shell
php examples/outputFormat/sarif.php --bootstrap examples/outputFormat/bootstrap.php --converter-class MyPhpLintConverter --verbose
```

[sarifweb]: https://sarifweb.azurewebsites.net/
[sarif-php-converters]: https://github.com/llaville/sarif-php-converters
[examples-format-folder]: https://github.com/overtrue/phplint/tree/9.4/examples/outputFormat
[var-export]: https://www.php.net/manual/en/function.var-export.php