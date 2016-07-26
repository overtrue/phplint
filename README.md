PHPLint
=======

[![StyleCI](https://styleci.io/repos/64124312/shield)](https://styleci.io/repos/64124312)
[![Build Status](https://travis-ci.org/overtrue/phplint.svg?branch=master)](https://travis-ci.org/overtrue/phplint)
[![Latest Stable Version](https://poser.pugx.org/overtrue/phplint/v/stable.svg)](https://packagist.org/packages/overtrue/phplint) [![Total Downloads](https://poser.pugx.org/overtrue/phplint/downloads.svg)](https://packagist.org/packages/overtrue/phplint) [![Latest Unstable Version](https://poser.pugx.org/overtrue/phplint/v/unstable.svg)](https://packagist.org/packages/overtrue/phplint) [![License](https://poser.pugx.org/overtrue/phplint/license.svg)](https://packagist.org/packages/overtrue/phplint)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overtrue/phplint/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overtrue/phplint/?branch=master)

`phplint` is a tool that can speed up linting of php files by running several lint processes at once.


## Installation

```shell
$ composer require overtrue/phplint
```

## Usage

### CLI

```shell
$ ./vendor/bin/phplint ./ --exclude=vendor
```

more:

```shell
$ ./vendor/bin/phplint --help
```

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
//    '/path/to/foo.php' => 'Parse error: syntax error, unexpected '$key' (T_VARIABLE)  on line 168',
//    '/path/to/bar.php' => 'Parse error: syntax error, unexpected 'class' (T_CLASS), expecting ',' or ';'  on line 28',
//]
```

## License

MIT

