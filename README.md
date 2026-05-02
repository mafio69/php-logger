# mafio69/fast-php-logger

PSR-3 compliant dual logger for PHP 8.1+ with:
- date-based log file structure with configurable subdirectory pattern
- automatic log rotation by file size
- caller location in every log entry (`directory/file.php:line`)
- automatic anonymization of sensitive fields
- configurable minimum log level for file output
- configurable date format and timezone

## Installation

```sh
composer require mafio69/fast-php-logger
```

## Quick start

```php
use Mariusz\Logger\DualLogger;

// one-liner factory
$logger = DualLogger::create('./logs');

$logger->info('Server started');
$logger->warning('Login failed', ['email' => 'jan@example.com', 'token' => 'abc123xyz']);
```

Or with full control:

```php
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;
use Psr\Log\LogLevel;

$logger = new DualLogger(
    new LogFileManager('./logs'),
    minLevel: LogLevel::DEBUG,
);
```

Output:
```
[2026-05-02 01:54:00] [INFO]    [Bootstrap/App.php:12] Server started
[2026-05-02 01:54:00] [WARNING] [Auth/Service.php:88]  Login failed {"email":"jan****com","token":"a****yz"}
```

---

## Configuration

### LogFileManager

```php
new LogFileManager(
    logDir:        './logs',      // base log directory
    maxFileSize:   1048576,       // max file size in bytes before rotation (default: 1MB)
    maxFiles:      5,             // max number of rotated archives to keep (default: 5)
    prefix:        'app-',        // filename prefix  → app-2026-05-02.log
    suffix:        '-prod',       // filename suffix  → 2026-05-02-prod.log
    dateStructure: 'Y/m',         // subdirectory pattern (default: year/month)
)
```

#### dateStructure examples

| Value | Path |
|-------|------|
| `'Y/m'` (default) | `logs/2026/05/2026-05-02.log` |
| `'Y'` | `logs/2026/2026-05-02.log` |
| `'Y/m/d'` | `logs/2026/05/02/2026-05-02.log` |
| `''` | `logs/2026-05-02.log` (flat) |

#### prefix + suffix examples

| prefix | suffix | filename |
|--------|--------|----------|
| `'app-'` | `''` | `app-2026-05-02.log` |
| `''` | `'-prod'` | `2026-05-02-prod.log` |
| `'api-'` | `'-v2'` | `api-2026-05-02-v2.log` |

---

### DualLogger

```php
new DualLogger(
    fileManager: new LogFileManager('./logs'),
    minLevel:    LogLevel::WARNING,   // minimum level written to file (default: warning)
    dateFormat:  'Y-m-d H:i:s',      // timestamp format (default: ISO-like)
    timezone:    'Europe/Warsaw',     // timezone (default: system timezone)
)
```

#### minLevel examples

| minLevel | Written to file |
|----------|----------------|
| `LogLevel::DEBUG` | everything |
| `LogLevel::INFO` | info and above |
| `LogLevel::WARNING` (default) | warning, error, critical, alert, emergency |
| `LogLevel::ERROR` | error and above only |

#### dateFormat examples

| dateFormat | Output |
|------------|--------|
| `'Y-m-d H:i:s'` (default) | `2026-05-02 01:54:00` |
| `'d.m.Y H:i'` | `02.05.2026 01:54` |
| `'c'` | `2026-05-02T01:54:00+02:00` |

---

## Anonymization

Sensitive fields in log context are automatically masked — the middle portion is replaced with `****`,
keeping ~25% visible at each end so developers can identify values without exposing full data.

```php
$logger->warning('Login failed', [
    'email'    => 'jan.kowalski@gmail.com',  // → jan.****@gmail.com
    'token'    => 'supersecret123',           // → supe****t123
    'pesel'    => '12345678901',              // → 123****901
    'password' => 'ab',                       // → ****  (too short)
]);
```

Masked fields: `pesel`, `nip`, `ssn`, `passport`, `email`, `phone`, `telefon`, `password`,
`token`, `api_key`, `secret`, `session`, `card`, `iban`, `cvv`, `konto`, `adres`, `street` and more.

---

## STDERR suppression in tests

Set `APP_ENV=test` to suppress STDERR output during tests. In `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="test" force="true"/>
</php>
```

---

## Laravel

Auto-discovered via `extra.laravel.providers`. No manual registration needed.

Publish config:

```sh
php artisan vendor:publish --tag=fast-php-logger-config
```

`config/php-logger.php`:

```php
return [
    'log_dir'    => storage_path('logs'),
    'min_level'  => env('LOG_LEVEL', 'warning'),
    'date_format' => 'Y-m-d H:i:s',
    'timezone'   => env('APP_TIMEZONE', ''),
    'file' => [
        'max_file_size'  => 1048576,
        'max_files'      => 5,
        'prefix'         => '',
        'suffix'         => '',
        'date_structure' => 'Y/m',
    ],
];
```

Resolve from container:

```php
$logger = app(\Mariusz\Logger\DualLogger::class);
```

---

## Symfony

Register the bundle in `config/bundles.php`:

```php
return [
    Mariusz\Logger\Symfony\PhpLoggerBundle::class => ['all' => true],
];
```

Optional config in `config/packages/php_logger.yaml`:

```yaml
php_logger:
    log_dir:     '%kernel.logs_dir%'
    min_level:   warning
    date_format: 'Y-m-d H:i:s'
    timezone:    ''
    file:
        max_file_size:  1048576
        max_files:      5
        prefix:         ''
        suffix:         ''
        date_structure: 'Y/m'
```

Inject via autowiring:

```php
use Mariusz\Logger\DualLogger;

class MyService
{
    public function __construct(private DualLogger $logger) {}
}
```

---

## Testing

```sh
composer install
composer test
```
