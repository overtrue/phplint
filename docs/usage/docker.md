# Docker CLI

```shell
docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest lint ./ --exclude=vendor --configuration never --cache-adapter never
```

> Please mount your source code to `/workdir` in the container.

> [!IMPORTANT]
> Docker image with `latest` tag use the PHP 8.5 runtime !
