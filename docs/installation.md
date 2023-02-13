# Installation

1. [Requirements](#requirements)
1. [PHAR](#phar)
1. [Docker](#docker) 
1. [Phive](#phive)
1. [Composer](#composer)

## Requirements

| Version | Status                                    | Requirements   |
|:--------|:------------------------------------------|:---------------|
| **9.x** | **Active development**                    | **PHP >= 8.0** |
| 6.x     | Active support                            | PHP >= 8.2     |
| 5.x     | Active support                            | PHP >= 8.1     |
| 4.x     | Active support                            | PHP >= 8.0     |
| 3.x     | End Of Life                               | PHP >= 7.4     |

## PHAR

The preferred method of installation is to use the PHPLint PHAR which can be downloaded from the most recent
[Github Release][releases]. This method ensures you will not have any dependency conflict issue.

## Docker

You can install `phplint` with [Docker][docker]

```shell
docker pull overtrue/phplint:latest
```

## Phive

You can install `phplint` with [Phive][phive]

```shell
phive install overtrue/phplint --force-accept-unsigned
```

To upgrade `phplint` use the following command:

```shell
phive update overtrue/phplint --force-accept-unsigned
```

## Composer

You can install `phplint` with [Composer][composer]

```shell
composer global require overtrue/phplint
```

If you cannot install it because of a dependency conflict, or you prefer to install it for your project, we recommend
you to take a look at [bamarni/composer-bin-plugin][bamarni/composer-bin-plugin]. Example:

```shell
composer require --dev bamarni/composer-bin-plugin
composer bin phplint require --dev overtrue/phplint

vendor/bin/phplint
```

[releases]: https://github.com/overtrue/phplint/releases
[composer]: https://getcomposer.org
[bamarni/composer-bin-plugin]: https://github.com/bamarni/composer-bin-plugin
[phive]: https://github.com/phar-io/phive
[docker]: https://docs.docker.com/get-docker/
