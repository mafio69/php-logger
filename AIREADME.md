# AIREADME.md — mafio69/fast-php-logger

Machine-readable project summary for AI assistants (Copilot, Cursor, Kiro, etc.).

## Package identity

| Key | Value |
|-----|-------|
| Packagist | `mafio69/fast-php-logger` |
| GitHub | `https://github.com/mafio69/php-logger` |
| PHP namespace | `Mariusz\Logger` |
| PHP version | `>=8.1` |
| PSR | PSR-3 (`psr/log ^3.0`) |
| License | MIT |

## Entry point

```php
use Mariusz\Logger\DualLogger;

$logger = DualLogger::create('./logs');          // quick factory
$logger = new DualLogger(                        // full control
    new \Mariusz\Logger\LogFileManager('./logs'),
    minLevel:   \Psr\Log\LogLevel::WARNING,
    dateFormat: 'Y-m-d H:i:s',
    timezone:   'Europe/Warsaw',
);
```

## Classes

### `Mariusz\Logger\DualLogger`

Extends `Psr\Log\AbstractLogger`. Every `log()` call:
1. Writes to STDERR (suppressed when `APP_ENV=test`)
2. Writes to file if `level >= minLevel`

**Constructor**
```
DualLogger(
    ?LogFileManager $fileManager = null,
    string $minLevel   = 'warning',       // PSR-3 level string
    string $dateFormat = 'Y-m-d H:i:s',
    string $timezone   = '',              // '' = system timezone
)
```

**Static factory**
```
DualLogger::create(string $logDir, string $minLevel = 'warning', string $dateFormat = 'Y-m-d H:i:s', string $timezone = ''): self
```

**Log entry format**
```
[2026-05-02 01:54:00] [WARNING] [Auth/Service.php:88] Login failed {"email":"jan****com"}
```
Fields: `[timestamp] [LEVEL] [dir/file.php:line] message {json_context}`

**Private methods**
- `format()` — builds log entry string
- `resolveLocation()` — extracts `dir/file.php:line` from `debug_backtrace` depth 3
- `writeToStderr()` — writes to STDERR, skips when `APP_ENV=test`
- `meetsMinLevel()` — compares level integer values

**Level integer map**
```
debug=100, info=200, notice=300, warning=400, error=500, critical=600, alert=700, emergency=800
```

---

### `Mariusz\Logger\LogFileManager`

Writes log entries to date-based files with size rotation.

**Constructor**
```
LogFileManager(
    string $logDir,
    int    $maxFileSize   = 1048576,   // 1MB
    int    $maxFiles      = 5,
    string $prefix        = '',
    string $suffix        = '',
    string $dateStructure = 'Y/m',     // PHP date() pattern for subdirectory
)
```

**Path resolution** (`dateStructure` → path)
```
'Y/m'   → logs/2026/05/2026-05-02.log   (default)
'Y'     → logs/2026/2026-05-02.log
'Y/m/d' → logs/2026/05/02/2026-05-02.log
''      → logs/2026-05-02.log
```

**Filename with prefix/suffix**
```
prefix='app-', suffix=''      → app-2026-05-02.log
prefix='',     suffix='-prod' → 2026-05-02-prod.log
```

**Rotation** — when `filesize >= maxFileSize`:
shifts `.1`→`.2`→…→`.N` (max `maxFiles` archives), renames current to `.1`

**Private methods**: `resolvePath()`, `needsRotation()`, `ensureDirectory()`, `rotate()`

---

### `Mariusz\Logger\LogAnonymizer`

`final` class. Recursively masks sensitive string fields in context arrays.

**Masking rule** — keeps `~25%` from each end, replaces middle with `****`:
```
'jan.kowalski@gmail.com' → 'jan.****@gmail.com'
'supersecret123'         → 'supe****t123'
'12345678901'            → '123****901'
'ab'                     → '****'   (len <= 4)
```

**Sensitive field names** (case-insensitive match):
```
pesel, nip, regon, dowod, dowod_osobisty, id_card, passport, paszport, ssn, national_id,
email, e-mail, mail, telefon, phone, mobile, tel, numer_telefonu, phone_number,
password, haslo, hasło, passwd, pass,
token, access_token, refresh_token, api_key, apikey, secret, tajny, klucz, private_key,
authorization, auth, bearer, session, session_id, cookie,
karta, card, card_number, numer_karty, pan, cvv, cvc, expiry, iban, konto, account_number, numer_konta,
adres, address, ulica, street
```

**Public API**: `anonymize(array $context): array`

---

## Framework integrations

### Laravel

Auto-discovered. No manual registration needed (`extra.laravel.providers` in `composer.json`).

Service Provider: `Mariusz\Logger\Laravel\LoggerServiceProvider`
- Registers `DualLogger::class` as singleton
- Config key: `php-logger` (`config/php-logger.php`)
- Publish: `php artisan vendor:publish --tag=fast-php-logger-config`

Config shape:
```php
[
    'log_dir'     => storage_path('logs'),
    'min_level'   => env('LOG_LEVEL', 'warning'),
    'date_format' => 'Y-m-d H:i:s',
    'timezone'    => env('APP_TIMEZONE', ''),
    'file' => [
        'max_file_size'  => 1048576,
        'max_files'      => 5,
        'prefix'         => '',
        'suffix'         => '',
        'date_structure' => 'Y/m',
    ],
]
```

### Symfony

Bundle: `Mariusz\Logger\Symfony\PhpLoggerBundle`
Extension: `Mariusz\Logger\Symfony\DependencyInjection\PhpLoggerExtension`
Configuration: `Mariusz\Logger\Symfony\DependencyInjection\Configuration`

Register in `config/bundles.php`:
```php
Mariusz\Logger\Symfony\PhpLoggerBundle::class => ['all' => true],
```

Config key: `php_logger` (YAML). `log_dir` defaults to `%kernel.logs_dir%`.
Both `DualLogger` and `LogFileManager` are registered as public services, autowirable.

---

## File structure

```
src/
  DualLogger.php
  LogFileManager.php
  LogAnonymizer.php
  Laravel/
    LoggerServiceProvider.php
    config/php-logger.php
  Symfony/
    PhpLoggerBundle.php
    DependencyInjection/
      PhpLoggerExtension.php
      Configuration.php
tests/
  Unit/
    DualLoggerTest.php
    LogFileManagerTest.php
    LogAnonymizerTest.php
```

## Testing

```sh
composer test          # PHPUnit 11
APP_ENV=test           # suppresses STDERR in tests (set in phpunit.xml)
```

34 tests, 49 assertions. Uses `dg/bypass-finals` to mock `final` classes.

## Coding conventions

- `declare(strict_types=1)` everywhere
- `final` classes by default
- Constructor property promotion where applicable
- No `mixed`, no `echo`, no `var_dump`
- One class = one responsibility, one method = one verb
