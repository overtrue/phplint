# Contributing

We look forward to your contributions! Here are some examples how you can contribute:

- Report an issue
- Suggest a new feature
- Send a pull request

**WARNING** : Since version 9.1.0, PHPLint enforce QA by verify if the Application Version is the last one identified 
into the `src/Console/Application.php` file.
This verification is only executed when you try to push code to the remote repository.
To avoid such check, use the `git push --no-verify` syntax. 

This check is only for maintainers of this project to prepare a new release and forgot to bump `Application::VERSION`.

![pre-push git hook](./assets/pre-push-hook.png)

## Workflow for Pull Requests

1. Fork the repository.
1. Create your branch from `main` if you plan to implement new functionality or change existing code significantly.
1. Implement your change and add tests for it.
1. Ensure the test suite passes.
1. Ensure the code complies with our coding guidelines.
1. Send your Pull Request

## Fork the PHPLint repository

Before starting to contribute to this project, you first need to install code from GitHub:

```shell 
git clone --branch main https://github.com/overtrue/phplint.git
cd phplint 
composer update
```

In an effort to maintain a homogeneous code base, we strongly encourage contributors to run 
[PHPStan][phpstan], [PHP-CS-Fixer][php-cs-fixer] and [PHPUnit][phpunit] before submitting a Pull Request.

All dev tools (`phpstan`, `php-cs-fixer`, `phpunit`) are under control of [bamarni/composer-bin-plugin][bamarni/composer-bin-plugin].

## Static Code Analysis

Static analysis of source code is provided using [PHPStan][phpstan]

This project comes with a configuration file (located at `/phpstan.neon.dist` in the repository)
and an executable for PHPStan (located at `vendor/bin/phpstan`) that you can use to analyse your source code for compliance with this project's coding guidelines:

```shell
composer code:check
```

Here is a preview of what call look like:

![phpstan_run](./assets/phpstan_run.png)

## Coding standards

Coding standards are enforced using [PHP-CS-Fixer][php-cs-fixer]

This project comes with a configuration file (located at `/.php-cs-fixer.dist.php` in the repository) 
and an executable for PHP CS Fixer (located at `vendor/bin/php-cs-fixer`) that you can use to (re)format your source code for compliance with this project's coding guidelines:

```shell
composer style:fix
```

If you only want to check source code standard violation, without apply changes, please use instead: 

```shell
composer style:check
```

Here is a preview of what call look like:

![php-cs-fixer_dry-run](./assets/php-cs-fixer_dry-run.png)

## Running Tests

All tests must PASS before submitting a Pull Request.

Three Composer shortcuts are available:

```shell
composer tests:unit
```
Executes all unit tests (that include test suites: `cache`, `configuration`, `finder`)

```shell
composer tests:e2e
```
Execute end-to-end tests (that include test suite `e2e`)

```shell
composer tests:all
```
Execute all tests (unit and end-to-end)

[bamarni/composer-bin-plugin]: https://github.com/bamarni/composer-bin-plugin
[phpstan]: https://github.com/phpstan/phpstan
[php-cs-fixer]: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer
[phpunit]: https://github.com/sebastianbergmann/phpunit
