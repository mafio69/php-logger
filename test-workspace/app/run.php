<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogAnonymizer;
use Mariusz\Logger\LogContextSerializer;
use Mariusz\Logger\LogFileManager;
use Psr\Log\LogLevel;

$failures = 0;
$passes   = 0;

function check(bool $cond, string $msg): void
{
    global $failures, $passes;
    if ($cond) {
        echo "  \033[32m✓\033[0m $msg\n";
        $passes++;
    } else {
        echo "  \033[31m✗\033[0m $msg\n";
        $failures++;
    }
}

function section(string $name): void
{
    echo "\n--- $name ---\n";
}

function clearLogs(string $logDir): void
{
    if (!is_dir($logDir)) {
        return;
    }
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($logDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($rii as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($logDir);
}

function buildLogger(string $logDir, string $minLevel = LogLevel::DEBUG, string $timezone = 'UTC', string $dateStructure = 'Y/m'): DualLogger
{
    return new DualLogger(
        new LogFileManager($logDir, 1048576, 5, '', '', $dateStructure),
        new LogAnonymizer(),
        new LogContextSerializer(),
        new LoggerConfigDto($minLevel, 'Y-m-d H:i:s', $timezone, false, true),
    );
}

$logDir = __DIR__ . '/logs';
clearLogs($logDir);

$now            = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$year           = $now->format('Y');
$month          = $now->format('m');
$day            = $now->format('d');
$expectedLogFile = "{$logDir}/{$year}/{$month}/{$year}-{$month}-{$day}.log";

echo "PHP " . PHP_VERSION . " on smoke test for mafio69/fast-php-logger\n";
echo "Log directory: {$logDir}\n";

// === Test 1: All PSR-3 levels write to file at minLevel=DEBUG ===
section('Test 1: All 8 PSR-3 levels write to file at minLevel=DEBUG');

$logger = buildLogger($logDir, LogLevel::DEBUG);
$logger->debug('debug message', ['k' => 'd']);
$logger->info('info message', ['k' => 'i']);
$logger->notice('notice message', ['k' => 'n']);
$logger->warning('warning message', ['k' => 'w']);
$logger->error('error message', ['k' => 'e']);
$logger->critical('critical message', ['k' => 'c']);
$logger->alert('alert message', ['k' => 'a']);
$logger->emergency('emergency message', ['k' => 'em']);

check(file_exists($expectedLogFile), 'Log file created at expected path');

$content = file_get_contents($expectedLogFile);
check(str_contains($content, '[DEBUG]'), '[DEBUG] written');
check(str_contains($content, '[INFO]'), '[INFO] written');
check(str_contains($content, '[NOTICE]'), '[NOTICE] written');
check(str_contains($content, '[WARNING]'), '[WARNING] written');
check(str_contains($content, '[ERROR]'), '[ERROR] written');
check(str_contains($content, '[CRITICAL]'), '[CRITICAL] written');
check(str_contains($content, '[ALERT]'), '[ALERT] written');
check(str_contains($content, '[EMERGENCY]'), '[EMERGENCY] written');

// === Test 2: Min level filtering ===
section('Test 2: Min level WARNING filters out DEBUG/INFO/NOTICE');

unlink($expectedLogFile);
$logger = buildLogger($logDir, LogLevel::WARNING);

$logger->debug('skip me');
$logger->info('skip me');
$logger->notice('skip me');
$logger->warning('keep me');
$logger->error('keep me');

$content = file_get_contents($expectedLogFile);
check(str_contains($content, '[WARNING]'), 'WARNING kept');
check(str_contains($content, '[ERROR]'), 'ERROR kept');
check(!str_contains($content, '[DEBUG]'), 'DEBUG filtered out');
check(!str_contains($content, '[INFO]'), 'INFO filtered out');
check(!str_contains($content, '[NOTICE]'), 'NOTICE filtered out');

// === Test 3: Anonymization ===
section('Test 3: Sensitive fields are masked');

unlink($expectedLogFile);
$logger = buildLogger($logDir, LogLevel::DEBUG);

$logger->warning('login failed', [
    'email'    => 'jan.kowalski@gmail.com',
    'password' => 'supersecret123',
    'token'    => 'abc123def456',
    'safe'     => 'visible-as-is',
]);

$content = file_get_contents($expectedLogFile);
check(str_contains($content, '"safe":"visible-as-is"'), 'Non-sensitive field untouched');
check(!str_contains($content, 'jan.kowalski@gmail.com'), 'Full email NOT present');
check(!str_contains($content, 'supersecret123'), 'Full password NOT present');
check(!str_contains($content, 'abc123def456'), 'Full token NOT present');
check(str_contains($content, '.com'), 'Email TLD preserved (anonymized but readable)');

// === Test 4: Date structure ===
section('Test 4: Date structure Y/m creates year/month subdirs');

check(is_dir("{$logDir}/{$year}"), "Year directory exists: {$year}");
check(is_dir("{$logDir}/{$year}/{$month}"), "Month directory exists: {$year}/{$month}");
check(file_exists($expectedLogFile), "Day file exists: {$year}-{$month}-{$day}.log");

// === Test 5: Context serialization ===
section('Test 5: Context serialization (array, exception, object)');

unlink($expectedLogFile);
$logger = buildLogger($logDir, LogLevel::DEBUG);

$obj = new class () {
    public function __toString(): string
    {
        return 'stringable-object';
    }
};

$logger->error('order failed', [
    'order'     => ['id' => 42, 'items' => 3],
    'exception' => new RuntimeException('Connection refused'),
    'stringable' => $obj,
]);

$content = file_get_contents($expectedLogFile);
check(str_contains($content, '"id":42'), 'Nested array serialized as JSON');
check(str_contains($content, 'Connection refused'), 'Exception message preserved');
check(str_contains($content, 'RuntimeException'), 'Exception class name preserved');

// === Test 6: Timezone ===
section('Test 6: Timezone Europe/Warsaw produces UTC-equivalent timestamp');

unlink($expectedLogFile);
$logger = buildLogger($logDir, LogLevel::DEBUG, 'Europe/Warsaw');

$before = time();
$logger->info('tz test');
$after = time();

$content = file_get_contents($expectedLogFile);
preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $content, $m);
$logged = new DateTimeImmutable($m[1], new DateTimeZone('Europe/Warsaw'));
$loggedUtc = $logged->setTimezone(new DateTimeZone('UTC'))->getTimestamp();

$diff = max(abs($loggedUtc - $before), abs($loggedUtc - $after));
check($diff <= 5, "Logged timestamp matches UTC 'now' (diff: {$diff}s)");

// === Test 7: Caller location ===
section('Test 7: Caller location (file:line) appears in log');

check(str_contains($content, 'run.php'), 'Caller file (run.php) referenced');
check((bool) preg_match('/\[app\/run\.php:\d+\]/', $content), 'Caller line number included');

// === Test 8: Prefix and suffix ===
section('Test 8: filename prefix and suffix');

$prefixLogDir = __DIR__ . '/logs-prefix';
clearLogs($prefixLogDir);

$logger = new DualLogger(
    new LogFileManager($prefixLogDir, 1048576, 5, 'app-', '-prod', 'Y/m'),
    new LogAnonymizer(),
    new LogContextSerializer(),
    new LoggerConfigDto(LogLevel::DEBUG, 'Y-m-d H:i:s', 'UTC', false, true),
);

$logger->warning('prefix-suffix test');

$expectedPrefixedFile = "{$prefixLogDir}/{$year}/{$month}/app-{$year}-{$month}-{$day}-prod.log";
check(file_exists($expectedPrefixedFile), "Prefixed+suffix filename: app-{$year}-{$month}-{$day}-prod.log");

clearLogs($prefixLogDir);

// === Test 9: PSR-3 unknown level throws ===
section('Test 9: Unknown log level throws InvalidArgumentException');

$threw = false;
try {
    $logger->log('unknown_level', 'should throw');
} catch (InvalidArgumentException) {
    $threw = true;
} catch (Throwable $t) {
    if ($t instanceof InvalidArgumentException) {
        $threw = true;
    }
}
check($threw, 'Unknown level throws InvalidArgumentException (PSR-3 §1.1)');

// === Summary ===
echo "\n" . str_repeat('=', 50) . "\n";
echo "Passes:   $passes\n";
echo "Failures: $failures\n";
echo str_repeat('=', 50) . "\n";

exit($failures > 0 ? 1 : 0);
