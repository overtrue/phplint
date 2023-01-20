# Documentation

Full documentation may be found in `docs` folder in repository, and may be read online without to do anything else.

As alternative, you may generate a professional static site with [Material for MkDocs][mkdocs-material].

Configuration file `mkdocs.yaml` is available and if you have Docker support, the documentation site can be simply build
with following command: 

`docker run --rm -it -u "$(id -u):$(id -g)" -v ${PWD}:/docs squidfunk/mkdocs-material build --verbose`

## Goal

The PHPLint is a command line tool that can speed up linting of php files by running several lint processes at once.

## Usage

1. [Console CLI](#console-cli)
1. [Docker CLI](#docker-cli) 
1. [GitHub Actions](#github-actions)
1. [GitLab CI](#gitlab-ci)
1. [Other CI Pipelines](#other-ci-pipelines)
2. [Programmatically](#programmatically) 

### Console CLI

Linting PHP source files should be as simple as running `phplint` with one or more source paths (no config required!). 
It will however assume some defaults that you might want to change.

PHPLint will by default be looking in order for the file `.phplint.yml` in the current working directory.
You can use another filename by option: `--configuration=FILENAME` or `-c FILENAME`.

A basic configuration could be for example:

```yaml
path: ./src
jobs: 10
extensions:
  - php
exclude:
  - vendor
warning: true
memory-limit: -1
no-cache: true
```

> If you want to ignore the configuration file directives, you should specify option `--no-configuration`.

You can then find more advanced configuration settings in [the configuration documentation](configuration.md). 
For more information on which options are available, you can run: `phplint --help`

```text
Description:
  Lint something

Usage:
  phplint [options] [--] [<path>...]

Arguments:
  path                               Path to file or directory to lint [default: ["."]]

Options:
      --exclude=EXCLUDE              Path to file or directory to exclude from linting (multiple values allowed)
      --extensions=EXTENSIONS        Check only files with selected extensions [default: ["php"]]
  -j, --jobs=JOBS                    Number of paralleled jobs to run [default: 5]
  -c, --configuration=CONFIGURATION  Read configuration from config file [default: ".phplint.yml"]
      --no-configuration             Ignore default configuration file (.phplint.yml)
      --no-cache                     Ignore cached data
      --cache[=CACHE]                Path to the cache directory [default: ".phplint.cache"]
      --no-progress                  Hide the progress output
  -p, --progress=PROGRESS            Show the progress output [default: "printer"]
      --log-json[=LOG-JSON]          Log scan results in JSON format to file [default: "standard output"]
      --log-xml[=LOG-XML]            Log scan results in JUnit XML format to file [default: "standard output"]
  -w, --warning                      Also show warnings
  -q, --quiet                        Do not output any message
      --no-files-exit-code           Throw error if no files processed
  -h, --help                         Display help for the given command. When no command is given display help for the list command
  -V, --version                      Display this application version
      --ansi|--no-ansi               Force (or disable --no-ansi) ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Docker CLI

```shell
docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest ./ --exclude=vendor --no-configuration --no-cache
```

> Please mount your source code to `/workdir` in the container.

> Be carefully when you use the cache subsystem. Don't forget to specify `-u "$(id -u):$(id -g)"` arguments on `docker run` command, 
otherwise cache files (into `.phplint.cache` directory by default) will be created with `root` account.

### GitHub Actions

```yaml
uses: overtrue/phplint@main
with:
    path: .
    options: --exclude=vendor
```

### GitLab CI

```yaml
code-quality:lint-php:
    image: overtrue/phplint:latest
    variables:
        INPUT_PATH: "./"
        INPUT_OPTIONS: "-c .phplint.yml"
    script: echo '' #prevents ci yml parse error
```

### Other CI Pipelines

Run this command using `overtrue/phplint:latest` Docker image:

```shell
/root/.composer/vendor/bin/phplint ./ --exclude=vendor
```

### Programmatically

```php
use Overtrue\PHPLint\Linter;

$path = __DIR__ .'/app';
$exclude = ['vendor'];
$extensions = ['php'];
$warnings = true;

$linter = new Linter($path, $exclude, $extensions, $warnings);

// get errors
$errors = $linter->lintFiles();

//
// [
//    '/path/to/foo.php' => [
//          'error' => "unexpected '$key' (T_VARIABLE)",
//          'line' => 168,
//          'file' => '/path/to/foo.php',
//      ],
//    '/path/to/bar.php' => [
//          'error' => "unexpected 'class' (T_CLASS), expecting ',' or ';'",
//          'line' => 28,
//          'file' => '/path/to/bar.php',
//      ],
// ]

```

## Contributing

> Contribution are always welcome and much appreciated!. 

See [Contributor's Guide](contributing.md#contributing) before you start.

## Credits

Project originally created by [@overtrue](https://github.com/overtrue), which is now (since version 9.0) 
actively supported by [Laurent Laville (@llaville)](https://github.com/llaville)

[mkdocs-material]: https://github.com/squidfunk/mkdocs-material