# Diagnostic with custom provider

Since PHPLint 9.8, you have a `diagnose` command that may be :

- executed directly :

```shell
PLINT_DIAGNOSTIC=MyEnvProvider phplint diagnose -b examples/envProvider/bootstrap.php
```

- executed after the core `lint` command :

```shell
PLINT_DIAGNOSTIC=MyEnvProvider phplint lint -b examples/envProvider/bootstrap.php
```

If none of the seven embedded providers match your needs, you can create your own one (i.e: [MyEnvProvider]).

To see results produced by your provider, you have to : 

1. identity the FQCN class via the `PLINT_DIAGNOSTIC` environment variable
2. make it loadable, either by your autoloader, or by using the bootstrapping feature (`-b, --bootstrap` flag)

[MyEnvProvider]: https://github.com/overtrue/phplint/blob/9.8/examples/envProvider/MyEnvProvider.php
