# mariusz/php-logger

PSR-3 compliant dual logger for PHP 8.1+ with:
- date-based log file structure (`logs/{year}/{month}/{year-month-day}.log`)
- automatic log rotation by file size
- caller location in every log entry (`directory/file.php:line`)
- automatic anonymization of sensitive fields

## Installation

```sh
composer require mariusz/php-logger
```

## Usage

```php
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;

$logger = new DualLogger(
    new LogFileManager(logDir: './logs', maxFileSize: 1048576)
);

$logger->info('Server started');
$logger->warning('Login failed', ['email' => 'jan@example.com', 'token' => 'abc123xyz']);
```

Output:
```
[2026-05-02 01:54:00] [INFO]    [Bootstrap/App.php:12] Server started
[2026-05-02 01:54:00] [WARNING] [Auth/Service.php:88]  Login failed {"email":"jan****com","token":"a****yz"}
```

## Log structure

```
logs/
└── 2026/
    └── 05/
        ├── 2026-05-01.log
        ├── 2026-05-01.log.1   ← rotated archive
        └── 2026-05-02.log
```

All messages go to STDERR. `WARNING` and above are also written to file.
STDERR is suppressed when `APP_ENV=test`.

## Anonymized fields

`pesel`, `nip`, `ssn`, `passport`, `email`, `phone`, `telefon`, `password`, `token`,
`api_key`, `secret`, `session`, `card`, `iban`, `cvv`, `konto`, `adres`, `street` and more.

The middle portion of the value is replaced with `****`, keeping ~25% visible at each end:

```
jan.kowalski@gmail.com  →  jan.****@gmail.com
12345678901             →  123****901
supersecret123          →  supe****t123
ab                      →  ****
```

## Testing

```sh
composer install
composer test
```
