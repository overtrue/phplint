# Docker CLI

```shell
docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest ./ --exclude=vendor --no-configuration --no-cache
```

> Please mount your source code to `/workdir` in the container.

> Be carefully when you use the cache subsystem. Don't forget to specify `-u "$(id -u):$(id -g)"` arguments on `docker run` command,
otherwise cache files (into `.phplint.cache` directory by default) will be created with `root` account.

**IMPORTANT** : Docker image with `latest` tag use the PHP 8.2 runtime !
