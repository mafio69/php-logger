# Test workspace

Live smoke test for `mafio69/fast-php-logger`.

Runs `app/run.php` on PHP 8.1 inside Docker. The script bootstraps the logger
from the host source (via composer path repo + symlink), writes logs at every
PSR-3 level, exercises anonymization, date structure, timezone, prefix/suffix
and unknown-level errors — then asserts the resulting log file contents.

## Run

```bash
docker compose run --rm php
```

Exit code `0` → all checks pass, exit code `1` → at least one failed.

Logs are written to `app/logs/` (git-ignored). The file tree mirrors
`Y/m/YYYY-MM-DD.log`.

## What is wired

- `Dockerfile`     — `php:8.1-cli` + composer 2
- `docker-compose.yml` — mounts `./app` at `/app` and the **repo root** at `/repo`
- `app/composer.json` — pulls `mafio69/fast-php-logger` from `/repo` via path repo + symlink
- `app/run.php`    — the smoke test

## Editing the source

Changes in `src/` are picked up immediately on the next run — no rebuild
required. The bind mount + symlink chain is:

```
host (repo root)  ─bind-mount─►  /repo  (in container)
                                       ▲
                                       │ symlink
/app/vendor/mafio69/fast-php-logger ────┘
```

To reinstall after `composer.json` in the repo changes:

```bash
docker compose run --rm php composer update
```

## Not in distribution

`bin/build.php` only bundles files from `src/` and `vendor/psr/log/src/`.
`test-workspace/` is never included in `dist/fast-php-logger.php`.
