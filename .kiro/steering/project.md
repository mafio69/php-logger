# php-logger — Project Overview

## What This Is
A standalone Composer library (`mafio69/fast-php-logger`) providing a PSR-3 compliant dual logger for PHP 8.1+.
Extracted from `mariusz/mcp-server`. Intended for reuse in any PHP project.

## Stack
- PHP 8.1+
- `psr/log` ^3.0 — PSR-3 interface
- PHPUnit 11 — testing
- `dg/bypass-finals` — mocking final classes in tests

## Classes

### `DualLogger` (src/DualLogger.php)
Implements PSR-3 `AbstractLogger`. Writes all messages to STDERR and delegates messages at or above
`minLevel` to `LogFileManager`.

Constructor parameters:
- `fileManager` — optional `LogFileManager`
- `minLevel` — PSR-3 level string, default `warning`
- `dateFormat` — PHP date format string, default `Y-m-d H:i:s`
- `timezone` — timezone string, default system timezone

Private methods (one responsibility each):
- `format()` — builds the log entry string
- `resolveLocation()` — extracts caller file/line from backtrace
- `writeToStderr()` — writes to STDERR (suppressed when `APP_ENV=test`)
- `meetsMinLevel()` — checks if level meets threshold

### `LogFileManager` (src/LogFileManager.php)
Writes log messages to date-based files with automatic size rotation.

Constructor parameters:
- `logDir` — base directory, e.g. `./logs`
- `maxFileSize` — bytes before rotation, default `1048576` (1MB)
- `maxFiles` — max rotated archives, default `5`
- `prefix` — filename prefix, e.g. `app-` → `app-2026-05-02.log`
- `suffix` — filename suffix, e.g. `-prod` → `2026-05-02-prod.log`
- `dateStructure` — subdirectory pattern, default `Y/m`

Private methods (one responsibility each):
- `resolvePath()` — builds current log file path
- `needsRotation()` — checks file size
- `ensureDirectory()` — creates directory if missing
- `rotate()` — shifts archive files

dateStructure examples:
- `Y/m` → `logs/2026/05/2026-05-02.log` (default)
- `Y` → `logs/2026/2026-05-02.log`
- `Y/m/d` → `logs/2026/05/02/2026-05-02.log`
- `` → `logs/2026-05-02.log` (flat)

### `LogAnonymizer` (src/LogAnonymizer.php)
Masks sensitive fields in log context arrays. Replaces the middle ~50% with `****`.
Works recursively on nested arrays. Non-string values are left untouched.

Sensitive fields include: `pesel`, `nip`, `ssn`, `passport`, `email`, `phone`, `telefon`,
`password`, `token`, `api_key`, `secret`, `session`, `card`, `iban`, `cvv`, `konto`, `adres`, `street` and more.

## Coding Conventions
- `declare(strict_types=1)` in every file
- `final` classes by default
- Constructor property promotion where applicable
- One class = one responsibility
- One method = one verb (format, resolve, write, rotate, ensure, check…)
- No `mixed`, no `echo`, no `var_dump`

## Testing
```sh
composer install
composer test
# or
vendor/bin/phpunit
```

Tests in `tests/Unit/`, mirroring `src/`. PHPUnit 11 + `dg/bypass-finals`.
STDERR suppressed via `APP_ENV=test` in `phpunit.xml`.

Current coverage: 34 tests, 49 assertions.

## Relation to mcp-serv
This package is used by `mariusz/mcp-server` (sibling directory `../mcp-serv`) via a Composer path repository.
When published to Packagist, change `"@dev"` to `"^1.0"` and remove the `repositories` entry in mcp-serv's `composer.json`.

## What To Do Next (suggestions)
- Publish to Packagist
- Add Laravel Service Provider (`src/Laravel/LoggerServiceProvider.php`)
- Add a static factory `DualLogger::create('./logs')` for quick setup
- Tag first release: `git tag v1.0.0`
