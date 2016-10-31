PHPLint
=======

[![StyleCI](https://styleci.io/repos/64124312/shield)](https://styleci.io/repos/64124312)
[![Build Status](https://travis-ci.org/overtrue/phplint.svg?branch=master)](https://travis-ci.org/overtrue/phplint)
[![Latest Stable Version](https://poser.pugx.org/overtrue/phplint/v/stable.svg)](https://packagist.org/packages/overtrue/phplint) [![Total Downloads](https://poser.pugx.org/overtrue/phplint/downloads.svg)](https://packagist.org/packages/overtrue/phplint) [![Latest Unstable Version](https://poser.pugx.org/overtrue/phplint/v/unstable.svg)](https://packagist.org/packages/overtrue/phplint) [![License](https://poser.pugx.org/overtrue/phplint/license.svg)](https://packagist.org/packages/overtrue/phplint)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overtrue/phplint/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overtrue/phplint/?branch=master)

`phplint` is a tool that can speed up linting of php files by running several lint processes at once.


## Installation

```shell
$ composer require overtrue/phplint -vvv
```

## Usage

### CLI

```shell
Usage:
  phplint [options] [--] <path> (<path>)...

Arguments:
  path                               Path to file or directory to lint.

Options:
      --exclude=EXCLUDE              Path to file or directory to exclude from linting (multiple values allowed)
      --extensions=EXTENSIONS        Check only files with selected extensions (default: php)
  -j, --jobs=JOBS                    Number of parraled jobs to run (default: 5)
  -c, --configuration=CONFIGURATION  Read configuration from config file (default: ./.phplint.yml).
      --no-configuration             Ignore default configuration file (default: ./.phplint.yml).
      --no-cache                     Ignore cached data.
  -h, --help                         Display this help message
  -q, --quiet                        Do not output any message
  -V, --version                      Display this application version
      --ansi                         Force ANSI output
      --no-ansi                      Disable ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
 Lint something
```

example:

```shell
$ ./vendor/bin/phplint ./ --exclude=vendor
```

You can also define configuration as a file `.phplint.yml`:

```yaml
path: ./
jobs: 10
extensions:
  - php
exclude:
  - vendor
```

```shell
$ ./vendor/bin/phplint
```

By default, the command will read configuration from file `.phplint.yml` of path specified, you can custom the filename by option: `--configuration=FILENAME` or `-c=FILENAME`;

if you want do disabled any config file, you can add option `--no-configuration`.

### Program

```php
use Overtrue\PHPLint\Linter;

$path = __DIR__ .'/app';
$exclude = ['vendor'];
$extensions = ['php'];

$linter = new Linter($path, $exclude, $extensions);

// get errors
$errors = $linter->lint();

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

## License

MIT

