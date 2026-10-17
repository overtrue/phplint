# Installation


1. [Requirements](#requirements)
2. [PHAR](#phar)
3. [Docker](#docker) 
4. [Phive](#phive)
5. [Composer](#composer)


## Requirements


| Version | Status                 | Requirements   |
|:--------|:-----------------------|:---------------|
| **9.x** | **Active development** | **PHP >= 8.2** |
| 9.7     | Active support         | PHP >= 8.2     |
| 9.6     | Active support         | PHP >= 8.1     |
| 9.5     | End Of Life            | PHP >= 8.1     |
| 9.4     | End Of Life            | PHP >= 8.1     |
| 9.3     | End Of Life            | PHP >= 8.1     |
| 9.2     | End Of Life            | PHP >= 8.1     |
| 9.1     | End Of Life            | PHP >= 8.1     |
| 9.0     | End Of Life            | PHP >= 8.0     |
| 6.x     | End Of Life            | PHP >= 8.2     |
| 5.x     | End Of Life            | PHP >= 8.1     |
| 4.x     | End Of Life            | PHP >= 8.0     |
| 3.x     | End Of Life            | PHP >= 7.4     |


## PHAR


The preferred method of installation is to use the PHPLint PHAR which can be downloaded from the most recent
[Github Release][releases]. This method ensures you will not have any dependency conflict issue.


**IMPORTANT** : The PHAR distribution includes its Composer dependencies and requires PHP 8.2 or newer.


## Docker


You can install `phplint` with [Docker][docker]


```shell
docker pull overtrue/phplint:latest
```


## Phive


You can install `phplint` globally with [Phive][phive]


```shell
phive install overtrue/phplint --force-accept-unsigned
```


To upgrade global `phplint` use the following command:


```shell
phive update overtrue/phplint --force-accept-unsigned
```


You can also install `phplint` locally to your project with [Phive][phive] and configuration file `.phive/phars.xml`


```xml
<?xml version="1.0" encoding="UTF-8"?>
<phive xmlns="https://phar.io/phive">
    <phar name="overtrue/phplint" version="^9.7" copy="false" />
