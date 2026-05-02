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

## Without Composer

> **No Composer? No problem.**
> A pre-built single file is available on every [GitHub Release](https://github.com/mafio69/php-logger/releases).
> Download `fast-php-logger.php`, drop it anywhere in your project, and you're done.

```php
require_once 'fast-php-logger.php';

$logger = \Mariusz\Logger\DualLogger::create('./logs');
$logger->info('Hello');
```

- Zero dependencies — PSR-3 interfaces are bundled inside
- Works with PHP 8.1+
- Identical API to the Composer version

**Building the file yourself:**

```sh
git clone https://github.com/mafio69/php-logger.git
cd php-logger
composer install   # only needed once, to fetch psr/log sources
php bin/build.php  # generates dist/fast-php-logger.php
```

### Full configuration (no Composer)

```php
require_once 'fast-php-logger.php';

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;
use Psr\Log\LogLevel;

$logger = new DualLogger(
    new LogFileManager(
        logDir:        './logs',
        maxFileSize:   1048576,       // 1MB before rotation
        maxFiles:      5,             // keep 5 archives
        prefix:        'app-',        // → app-2026-05-02.log
        suffix:        '',
        dateStructure: 'Y/m',         // → logs/2026/05/
    ),
    minLevel:         LogLevel::WARNING,    // only warning and above go to file
    dateFormat:       'Y-m-d H:i:s',
    timezone:         'Europe/Warsaw',
    stderrEnabled:    true,                 // set false to disable STDERR entirely
    stderrSkipInTest: true,                 // suppress STDERR when APP_ENV=test
);
```

## Quick start

```php
$logger->info('Server started');
```
[2026-05-02 01:54:00] [INFO]    [Bootstrap/App.php:12] Server started
  
```php
$logger->warning('Login failed', ['email' => 'jan@example.com', 'token' => 'abc123xyz']);
```
[2026-05-02 01:54:00] [WARNING] [Auth/Service.php:88]  Login failed {"email":"jan****com","token":"a****yz"}
  

```php
$logger->error('Order failed', [
    'order' => ['id' => 42, 'items' => 3],          // nested array — serialized as-is
    'user'  => new User(id: 7, name: 'Jan'),         // object — public properties dumped
]);
```
```
[2026-05-02 01:54:00] [ERROR]   [Shop/Service.php:55]  Order failed {"order":{"id":42,"items":3},"user":{"class":"User","id":7,"name":"Jan"}}
```
```php
$logger->critical('Unexpected error', ['exception' => $e]); // Exception → class, message, file:line, previous
```
```
[2026-05-02 01:54:00] [CRITICAL] [App/Handler.php:33] Unexpected error {"exception":{"class":"RuntimeException","message":"Connection refused","code":0,"file":"DB/Connection.php:42"}}
```

**Just pass your data — the logger does the rest:**
- 📍 caller location added automatically (`Auth/Service.php:88`)
- 🔒 sensitive fields masked automatically — no extra code needed
- 📁 written to a dated file (`logs/2026/05/2026-05-02.log`) and STDERR at the same time

Need more control? Use the full constructor — but you probably won't need to:

```php
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;
use Psr\Log\LogLevel;

$logger = new DualLogger(
    new LogFileManager('./logs', maxFileSize: 512000, maxFiles: 10),
    minLevel: LogLevel::DEBUG,
);
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
    fileManager:      new LogFileManager('./logs'),
    minLevel:         LogLevel::WARNING,   // minimum level written to file (default: warning)
    dateFormat:       'Y-m-d H:i:s',      // timestamp format (default: ISO-like)
    timezone:         'Europe/Warsaw',     // timezone (default: system timezone)
    stderrEnabled:    true,               // write to STDERR at all (default: true)
    stderrSkipInTest: true,               // suppress STDERR when APP_ENV=test (default: true)
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

#### stderrEnabled / stderrSkipInTest

| stderrEnabled | stderrSkipInTest | Behaviour |
|---------------|-----------------|-----------|
| `true` (default) | `true` (default) | STDERR active, suppressed when `APP_ENV=test` |
| `true` | `false` | STDERR always active, even in tests |
| `false` | *(ignored)* | STDERR disabled entirely |

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

By default STDERR is suppressed when `APP_ENV=test`. Set in `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="test" force="true"/>
</php>
```

To disable STDERR entirely (regardless of environment):

```php
new DualLogger(stderrEnabled: false);
```

To keep STDERR active even in tests:

```php
new DualLogger(stderrSkipInTest: false);
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
    'stderr' => [
        'enabled'      => env('PHP_LOGGER_STDERR', true),
        'skip_in_test' => true,
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
    stderr:
        enabled:      true
        skip_in_test: true
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
