# Installation Guide

> [!NOTE]
> Latest and old versions compatibility are listed on a [dedicated page](supported_versions.md).

1. [PHAR](#phar)
2. [Docker](#docker) 
3. [Phive](#phive)
4. [Composer](#composer)

## PHAR

The preferred method of installation is to use the **PHPLint** PHAR which can be downloaded from the most recent
[Github Release][releases]. This method ensures you will not have any dependency conflict issue.

> [!IMPORTANT]
> Dependencies included in PHAR distribution are PHP 8.2 compatible !

## Docker

You can install **PHPLint** with [Docker][docker] images available on the [Docker Hub][docker-hub].

```shell
docker pull overtrue/phplint:latest
```

## Phive

You can install **PHPLint** globally with [Phive][phive]

```shell
phive install overtrue/phplint --force-accept-unsigned
```

To upgrade global installation, use the following command:

```shell
phive update overtrue/phplint --force-accept-unsigned
```

You can also install **PHPLint** locally to your project with [Phive][phive] and configuration file `.phive/phars.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phive xmlns="https://phar.io/phive">
    <phar name="overtrue/phplint" version="^9.8" copy="false" />
</phive>
```

```shell
phive install --force-accept-unsigned
```

## Composer

You can install **PHPLint** with [Composer][composer]

```shell
composer global require overtrue/phplint ^9.8
```

If you cannot install it because of a dependency conflict, or you prefer to install it for your project, we recommend
you to take a look at [bamarni/composer-bin-plugin][bamarni/composer-bin-plugin]. 

For example:

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
[docker-hub]: https://hub.docker.com/r/overtrue/phplint
