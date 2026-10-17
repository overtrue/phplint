# Benchmarks

- With previous versions of PHPLint until 9.7, we used the [PHP Benchmarking framework][phpbench].

- With version PHPLint 9.8, we use now the command-line benchmarking tool [Hyperfine][hyperfine] and the most recent [version 2.0][hyperfine-v2].

## Source code to analyze

> [!CAUTION]
> The most important is to use the same source code, whatever it is.
>
> Here we will check one of my project with it's recent update : [SARIF PHP Converters][sarif-php-converters-1.8] 1.8.1

## Benchmarking protocol

> [!CAUTION]
> To compare speed execution of two different version of the same application, we should be in same context.
>
> Same PHPLint extensions loaded : 
>
> - no progress bar (`--no-progress` flag), to get only `output` format available with version 9.7
> - no log (`PLINT_LOGGER='Psr\Log\NullLogger'`) produced with version 9.8
> - `PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager` environment variables set with version 9.8
> - `PLINT_DIAGNOSTIC=never` environment variable set to avoid running auto diagnostic (default behavior) with version 9.8

<!-- admonition separator -->

> [!NOTE]
> By default (`-e,--env` platform environment) with the Symfony Console Application, we are in `dev` context.
>
> That means, with PHPLint 9.8, all available extensions are allowed and may be loaded depending of arguments provided on command line.
> And we want only the `output_manager` (equivalent to `output` format in v9.7) 
>
> Learn more with the [FAQ](FAQ.md)

Now, if the pre-requires is understood, we can prepare the Hyperfine command line :

### Warmup environment

Read official documentation at <https://github.com/sharkdp/hyperfine#warmup-runs-and-preparation-commands>

**The choice retained:** Three warmup runs before the running the actual benchmark (`--warmup 3`) 
and be sure to execute ten benchmarking runs (`--runs 10`).

### Choosing metrics and units

Read official documentation at <https://github.com/sharkdp/hyperfine#choosing-metrics-and-units>

**The choice retained:** User and System time in milliseconds (`--metrics time_user:ms,time_system:ms`).

### Parameterized benchmarks

Read official documentation at <https://github.com/sharkdp/hyperfine#parameterized-benchmarks>

**The choice retained:** Control the PHPLint `jobs` flag varying from 1 to 5 (`-P jobs 1 5` and `{jobs}` placeholder in command line).

### Environment variables

Read official documentation at <https://github.com/sharkdp/hyperfine#environment-variables>

**The choice retained:** With PHPLint 9.8 only (`--env "PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager"`)

### Final Hyperfine command to execute

> With PHPLint version 9.7.3 (61d8ea7)

```shell
hyperfine --warmup 3 --runs 10 --metrics time_user:ms,time_system:ms -P jobs 1 5 'bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs {jobs}'
```

> With PHPLint version 9.8.9999999.9999999-dev (35df3f5)

```shell
hyperfine --warmup 3 --runs 10  --metrics time_user:ms,time_system:ms -P jobs 1 5 --env "PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager" 'bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs {jobs}'
```

## Benchmarks results

> [!NOTE]
> On my platform :
> 
> - Ubuntu 24.4 LTS on x86_64 architecture within Docker Container running on Windows 11 / WSL2
> - One version before PHP 8.3 : PHP 8.2.34
> - One version after PHP 8.3 : PHP 8.5.11
>
> PHP add support to linting multiple files at once (`php -l` syntax) on CLI since version 8.3. 
> See [faster process linter request][phplint-request-197] for first implementation on PHPLint 9.7

### PHPLint 9.7 and PHP 8.2

```text
Benchmark 1: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 1 (10 runs)
                  mean     ±       σ          min     …     max
  User time     1110.3 ms  ±    42.0 ms    1038.4 ms  …  1165.5 ms
  System time    607.9 ms  ±    42.6 ms     548.4 ms  …   683.1 ms

Benchmark 2: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 2 (10 runs)
                  mean     ±       σ          min     …     max
  User time      801.5 ms  ±    27.8 ms     742.7 ms  …   846.8 ms           -27.8%
  System time    559.9 ms  ±    31.0 ms     492.1 ms  …   609.0 ms            -7.9%

Benchmark 3: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 3 (10 runs)
                  mean     ±       σ          min     …     max
  User time      695.1 ms  ±    29.7 ms     663.0 ms  …   744.2 ms           -37.4%  (1.6x faster)
  System time    587.0 ms  ±    32.6 ms     525.4 ms  …   629.1 ms            -3.4%

Benchmark 4: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 4 (10 runs)
                  mean     ±       σ          min     …     max
  User time      678.0 ms  ±    24.1 ms     652.8 ms  …   721.4 ms           -38.9%  (1.6x faster)
  System time    629.7 ms  ±    11.0 ms     612.9 ms  …   645.3 ms            +3.6%

Benchmark 5: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 5 (10 runs)
                  mean     ±       σ          min     …     max
  User time      732.6 ms  ±    41.2 ms     652.6 ms  …   804.8 ms           -34.0%  (1.5x faster)
  System time    675.2 ms  ±    34.2 ms     642.4 ms  …   758.3 ms           +11.1%
```

With following dependencies :

```text
bamarni/composer-bin-plugin               1.9.1  No conflicts for your bin dependencies
jetbrains/phpstorm-stubs                  2026.2 PHP runtime & extensions header files for PhpStorm
php-parallel-lint/php-console-highlighter 1.0.0  Highlight PHP code in terminal
symfony/cache                             7.4.21 Provides extended PSR-6, PSR-16 (and tags) implementations
symfony/console                           7.4.21 Eases the creation of beautiful and testable command line interfaces
symfony/event-dispatcher                  7.4.17 Provides tools that allow your application components to communicate with each other by di...
symfony/finder                            7.4.20 Finds files and directories via an intuitive fluent interface
symfony/options-resolver                  7.4.8  Provides an improved replacement for the array_replace PHP function
symfony/process                           7.4.21 Executes commands in sub-processes
symfony/yaml                              7.4.21 Loads and dumps YAML files
```

### PHPLint 9.8 and PHP 8.2

```text
Benchmark 1: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 1 (10 runs)
                  mean     ±       σ          min     …     max
  User time     1115.3 ms  ±    45.5 ms    1049.0 ms  …  1182.4 ms
  System time    669.3 ms  ±    47.9 ms     606.7 ms  …   753.4 ms

Benchmark 2: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 2 (10 runs)
                  mean     ±       σ          min     …     max
  User time      796.9 ms  ±    30.4 ms     756.7 ms  …   844.9 ms           -28.6%
  System time    619.4 ms  ±    23.3 ms     589.4 ms  …   665.5 ms            -7.5%

Benchmark 3: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 3 (10 runs)
                  mean     ±       σ          min     …     max
  User time      730.0 ms  ±    33.6 ms     673.1 ms  …   782.9 ms           -34.5%  (1.5x faster)
  System time    623.3 ms  ±    42.9 ms     545.3 ms  …   689.5 ms            -6.9%

Benchmark 4: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 4 (10 runs)
                  mean     ±       σ          min     …     max
  User time      704.8 ms  ±    37.6 ms     630.9 ms  …   773.9 ms           -36.8%  (1.6x faster)
  System time    644.2 ms  ±    37.5 ms     563.5 ms  …   702.3 ms            -3.8%

Benchmark 5: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 5 (10 runs)
                  mean     ±       σ          min     …     max
  User time      741.5 ms  ±    27.9 ms     692.7 ms  …   793.0 ms           -33.5%  (1.5x faster)
  System time    727.6 ms  ±    29.0 ms     687.5 ms  …   792.3 ms            +8.7%
```

With following dependencies : 

```text
bamarni/composer-bin-plugin 1.9.1  No conflicts for your bin dependencies
jetbrains/phpstorm-stubs    2026.2 PHP runtime & extensions header files for PhpStorm
psr/log                     3.0.2  Common interface for logging libraries
symfony/cache               7.4.21 Provides extended PSR-6, PSR-16 (and tags) implementations
symfony/console             7.4.21 Eases the creation of beautiful and testable command line interfaces
symfony/event-dispatcher    7.4.17 Provides tools that allow your application components to communicate with each other by dispatching even...
symfony/finder              7.4.20 Finds files and directories via an intuitive fluent interface
symfony/options-resolver    7.4.8  Provides an improved replacement for the array_replace PHP function
symfony/process             7.4.21 Executes commands in sub-processes
symfony/yaml                7.4.21 Loads and dumps YAML files
```

### PHPLint 9.7 and PHP 8.5

```text
Benchmark 1: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 1 (10 runs)
                  mean     ±       σ          min     …     max
  User time     1219.2 ms  ±    36.4 ms    1172.7 ms  …  1277.8 ms
  System time    489.9 ms  ±    44.0 ms     419.1 ms  …   545.2 ms

Benchmark 2: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 2 (10 runs)
                  mean     ±       σ          min     …     max
  User time      503.2 ms  ±    34.0 ms     442.9 ms  …   556.8 ms           -58.7%  (2.4x faster)
  System time    248.1 ms  ±    31.9 ms     199.7 ms  …   294.5 ms           -49.4%  (2.0x faster)

Benchmark 3: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 3 (10 runs)
                  mean     ±       σ          min     …     max
  User time      352.5 ms  ±    22.2 ms     327.1 ms  …   387.7 ms           -71.1%  (3.5x faster)
  System time    186.5 ms  ±    24.3 ms     141.6 ms  …   212.8 ms           -61.9%  (2.6x faster)

Benchmark 4: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 4 (10 runs)
                  mean     ±       σ          min     …     max
  User time      273.1 ms  ±    16.2 ms     249.1 ms  …   302.0 ms           -77.6%  (4.5x faster)
  System time    150.2 ms  ±    16.2 ms     121.0 ms  …   172.9 ms           -69.3%  (3.3x faster)

Benchmark 5: bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --no-configuration --exclude vendor --no-cache --jobs 5 (10 runs)
                  mean     ±       σ          min     …     max
  User time      255.9 ms  ±    16.8 ms     220.6 ms  …   281.0 ms           -79.0%  (4.8x faster)
  System time    132.5 ms  ±    22.4 ms     101.4 ms  …   178.4 ms           -73.0%  (3.7x faster)
```

With following dependencies :

```text
bamarni/composer-bin-plugin               1.9.1  No conflicts for your bin dependencies
jetbrains/phpstorm-stubs                  2026.2 PHP runtime & extensions header files for PhpStorm
php-parallel-lint/php-console-highlighter 1.0.0  Highlight PHP code in terminal
symfony/cache                             8.1.9  Provides extended PSR-6, PSR-16 (and tags) implementations
symfony/console                           8.1.9  Eases the creation of beautiful and testable command line interfaces
symfony/event-dispatcher                  8.1.5  Provides tools that allow your application components to communicate with each other by di...
symfony/finder                            8.1.8  Finds files and directories via an intuitive fluent interface
symfony/options-resolver                  8.1.0  Provides an improved replacement for the array_replace PHP function
symfony/process                           8.1.9  Executes commands in sub-processes
symfony/yaml                              8.1.9  Loads and dumps YAML files
```

### PHPLint 9.8 and PHP 8.5

```text
Benchmark 1: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 1 (10 runs)
                  mean     ±       σ          min     …     max
  User time     1237.4 ms  ±    59.9 ms    1125.9 ms  …  1321.4 ms
  System time    550.1 ms  ±    40.3 ms     507.3 ms  …   645.3 ms

Benchmark 2: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 2 (10 runs)
                  mean     ±       σ          min     …     max
  User time      540.2 ms  ±    23.4 ms     512.7 ms  …   581.1 ms           -56.3%  (2.3x faster)
  System time    263.9 ms  ±    23.0 ms     222.4 ms  …   295.5 ms           -52.0%  (2.1x faster)

Benchmark 3: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 3 (10 runs)
                  mean     ±       σ          min     …     max
  User time      386.5 ms  ±    22.7 ms     333.4 ms  …   407.5 ms           -68.8%  (3.2x faster)
  System time    187.0 ms  ±    25.1 ms     157.2 ms  …   237.4 ms           -66.0%  (2.9x faster)

Benchmark 4: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 4 (10 runs)
                  mean     ±       σ          min     …     max
  User time      309.1 ms  ±    26.2 ms     258.9 ms  …   329.7 ms           -75.0%  (4.0x faster)
  System time    164.6 ms  ±    22.6 ms     139.3 ms  …   204.8 ms           -70.1%  (3.3x faster)

Benchmark 5: PLINT_LOGGER='Psr\Log\NullLogger' PLINT_DIAGNOSTIC=never PLINT_ALLOW_PLUGINS=output_manager PLINT_DEFAULT_PLUGINS=output_manager bin/phplint lint /shared/backups/bartlett/sarif-php-converters/src/ --configuration never --exclude vendor --jobs 5 (10 runs)
                  mean     ±       σ          min     …     max
  User time      279.2 ms  ±    16.5 ms     249.7 ms  …   305.7 ms           -77.4%  (4.4x faster)
  System time    149.4 ms  ±    21.2 ms     120.2 ms  …   183.4 ms           -72.8%  (3.7x faster)
```

With following dependencies :

```text
bamarni/composer-bin-plugin 1.9.1  No conflicts for your bin dependencies
jetbrains/phpstorm-stubs    2026.2 PHP runtime & extensions header files for PhpStorm
psr/log                     3.0.2  Common interface for logging libraries
symfony/cache               8.1.9  Provides extended PSR-6, PSR-16 (and tags) implementations
symfony/console             8.1.9  Eases the creation of beautiful and testable command line interfaces
symfony/event-dispatcher    8.1.5  Provides tools that allow your application components to communicate with each other by dispatching even...
symfony/finder              8.1.8  Finds files and directories via an intuitive fluent interface
symfony/options-resolver    8.1.0  Provides an improved replacement for the array_replace PHP function
symfony/process             8.1.9  Executes commands in sub-processes
symfony/yaml                8.1.9  Loads and dumps YAML files
```

[hyperfine]: https://github.com/sharkdp/hyperfine
[hyperfine-v2]: https://github.com/sharkdp/hyperfine/releases/tag/v2.0.0
[phpbench]: https://github.com/phpbench/phpbench
[sarif-php-converters-1.8]: https://github.com/llaville/sarif-php-converters/releases/tag/1.8.1
[phplint-request-197]: https://github.com/overtrue/phplint/issues/197
