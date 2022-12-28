<h1 align="center">PHPLint</h1>

<p align="center">`phplint` is a tool that can speed up linting of php files by running several lint processes at once.</p>

![artboard 1](https://user-images.githubusercontent.com/1472352/38774811-3f780ab6-40a3-11e8-9a0a-a8d06d2c6463.jpg)

[![Release Status](https://github.com/overtrue/phplint/actions/workflows/build-phar.yml/badge.svg)](https://github.com/overtrue/phplint/actions/workflows/build-phar.yml)
[![Latest Stable Version](https://poser.pugx.org/overtrue/phplint/v/stable.svg)](https://packagist.org/packages/overtrue/phplint) [![Total Downloads](https://poser.pugx.org/overtrue/phplint/downloads.svg)](https://packagist.org/packages/overtrue/phplint) [![Latest Unstable Version](https://poser.pugx.org/overtrue/phplint/v/unstable.svg)](https://packagist.org/packages/overtrue/phplint) [![License](https://poser.pugx.org/overtrue/phplint/license.svg)](https://packagist.org/packages/overtrue/phplint)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overtrue/phplint/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overtrue/phplint/?branch=master)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fovertrue%2Fphplint.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fovertrue%2Fphplint?ref=badge_shield)

[![Sponsor me](https://github.com/overtrue/overtrue/blob/master/sponsor-me-button-s.svg?raw=true)](https://github.com/sponsors/overtrue)


## Installation

### required
- PHP >= 8.0
- Composer >= 2.0

> if you are using php 7.4, please refer [the 7.4 branch](https://github.com/overtrue/phplint/tree/7.4).
>
> if you are using php 8.1, please refer [the 8.1 branch](https://github.com/overtrue/phplint/tree/8.1).
>
> if you are using php 8.2, please refer [the 8.2 branch](https://github.com/overtrue/phplint/tree/8.2).


### Locally, if you have PHP

```bash
composer require overtrue/phplint: --dev -vvv
```

### Locally, if you only have Docker

```bash
docker pull overtrue/phplint:8.0
```

## Usage

### CLI

```text
Description:
  Lint something

Usage:
  phplint [options] [--] [<path>...]

Arguments:
  path                               Path to file or directory to lint.

Options:
      --exclude=EXCLUDE              Path to file or directory to exclude from linting (multiple values allowed)
      --extensions=EXTENSIONS        Check only files with selected extensions (default: php)
  -j, --jobs=JOBS                    Number of parraled jobs to run (default: 5)
  -c, --configuration=CONFIGURATION  Read configuration from config file (default: ./.phplint.yml).
      --no-configuration             Ignore default configuration file (default: ./.phplint.yml).
      --no-cache                     Ignore cached data.
      --cache=CACHE                  Path to the cache file.
      --no-progress                  Hide the progress output.
      --json[=JSON]                  Path to store JSON results.
      --xml[=XML]                    Path to store JUnit XML results.
  -w, --warning                      Also show warnings.
  -q, --quiet                        Do not output any message
      --no-files-exit-code           Throw error if no files processed.
  -h, --help                         Display help for the given command. When no command is given display help for the list command
  -V, --version                      Display this application version
      --ansi|--no-ansi               Force (or disable --no-ansi) ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

example:

```bash
./vendor/bin/phplint ./ --exclude=vendor
```

You can also define configuration as a file `.phplint.yml`:

```yaml
path: ./
jobs: 10
cache: build/phplint.cache
extensions:
  - php
exclude:
  - vendor
warning: false
memory_limit: -1
```

```bash
./vendor/bin/phplint
```

By default, the command will read configuration from file `.phplint.yml` of path specified, you can use another filename by option: `--configuration=FILENAME` or `-c FILENAME`;

If you want to disable the config file, you can add option `--no-configuration`.

### Docker CLI

```bash
docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:8.0 ./  --exclude=vendor
```

> Please mount the code directory to `/workdir` in the container.

### Program

```php
use Overtrue\PHPLint\Linter;

$path = __DIR__ .'/app';
$exclude = ['vendor'];
$extensions = ['php'];
$warnings = true;

$linter = new Linter($path, $exclude, $extensions, $warnings);

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

### GitHub Actions

```yaml
uses: overtrue/phplint@8.0
with:
  path: .
  options: --exclude=*.log
```

### GitLab CI

```yaml
code-quality:lint-php:
  image: overtrue/phplint:8.0
  variables:
    INPUT_PATH: "./"
    INPUT_OPTIONS: "-c .phplint.yml"
  script: echo '' #prevents ci yml parse error
```

### Other CI/CD (f.e. Bitbucket Pipelines)

Run this command using `overtrue/phplint:8.0` Docker image:

```bash
/root/.composer/vendor/bin/phplint ./ --exclude=vendor
```

### Warnings

Not all linting problems are errors, PHP also has warnings, for example when using a `continue` statement within a
`switch` `case`. By default, these errors are not reported, but you can turn this on with the `warning` cli flag, or
by setting the `warning` to true in the configuration.

## :heart: Sponsor me

[![Sponsor me](https://github.com/overtrue/overtrue/blob/master/sponsor-me.svg?raw=true)](https://github.com/sponsors/overtrue)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/overtrue)

## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/overtrue)


## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT
