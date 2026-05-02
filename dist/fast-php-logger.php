<?php
/**
 * fast-php-logger — single-file build (v1.0.0-4-g9c68b62) — 2026-05-02
 * https://github.com/mafio69/php-logger
 *
 * Usage:
 *   require_once 'fast-php-logger.php';
 *   $logger = \Mariusz\Logger\DualLogger::create('./logs');
 */

declare(strict_types=1);
namespace Psr\Log;

/**
 * Describes log levels.
 */
class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
}

namespace Psr\Log;

/**
 * Describes a logger instance.
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data. The only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param mixed[] $context
     */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param mixed[] $context
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param mixed[] $context
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param mixed[] $context
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param mixed[] $context
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param mixed[] $context
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param mixed[] $context
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param mixed[] $context
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param mixed[] $context
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void;
}

namespace Psr\Log;

/**
 * This is a simple Logger trait that classes unable to extend AbstractLogger
 * (because they extend another class, etc) can include.
 *
 * It simply delegates all log-level-specific methods to the `log` method to
 * reduce boilerplate code that a simple Logger that does the same thing with
 * messages regardless of the error level has to implement.
 */
trait LoggerTrait
{
    /**
     * System is unusable.
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    abstract public function log($level, string|\Stringable $message, array $context = []): void;
}

namespace Psr\Log;

/**
 * This is a simple Logger implementation that other Loggers can inherit from.
 *
 * It simply delegates all log-level-specific methods to the `log` method to
 * reduce boilerplate code that a simple Logger that does the same thing with
 * messages regardless of the error level has to implement.
 */
abstract class AbstractLogger implements LoggerInterface
{
    use LoggerTrait;
}

namespace Mariusz\Logger;

/**
 * Anonymizes sensitive fields in log context arrays.
 * Replaces the middle portion of sensitive values with **** to preserve
 * readability while protecting personal/sensitive data.
 */
final class LogAnonymizer
{
    /**
     * Field names (case-insensitive) whose values will be partially masked.
     * Covers Polish and international naming conventions.
     */
    private const SENSITIVE_FIELDS = [
        // Identyfikatory osobowe / Personal identifiers
        'pesel', 'nip', 'regon', 'dowod', 'dowod_osobisty', 'id_card',
        'passport', 'paszport', 'ssn', 'national_id',

        // Kontakt / Contact
        'email', 'e-mail', 'mail', 'telefon', 'phone', 'mobile',
        'tel', 'numer_telefonu', 'phone_number',

        // Uwierzytelnianie / Authentication
        'password', 'haslo', 'hasło', 'passwd', 'pass',
        'token', 'access_token', 'refresh_token', 'api_key', 'apikey',
        'secret', 'tajny', 'klucz', 'private_key',
        'authorization', 'auth', 'bearer',
        'session', 'session_id', 'cookie',

        // Płatności / Payment
        'karta', 'card', 'card_number', 'numer_karty', 'pan',
        'cvv', 'cvc', 'expiry', 'iban', 'konto', 'account_number',
        'numer_konta',

        // Adres / Address
        'adres', 'address', 'ulica', 'street',
    ];

    /**
     * Recursively anonymizes sensitive fields in a context array.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function anonymize(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->anonymize($value);
            } elseif (is_string($value) && $this->isSensitive($key)) {
                $context[$key] = $this->mask($value);
            }
        }

        return $context;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), self::SENSITIVE_FIELDS, true);
    }

    /**
     * Masks the middle of a string, keeping ~25% from each end visible.
     * Examples:
     *   "jan.kowalski@gmail.com" → "jan.****@gmail.com"  (not exact, illustrative)
     *   "12345678901"            → "123****901"
     *   "abc"                    → "****"
     */
    private function mask(string $value): string
    {
        $len = mb_strlen($value);

        if ($len <= 4) {
            return '****';
        }

        $visible = max(1, (int) round($len * 0.25));
        $start   = mb_substr($value, 0, $visible);
        $end     = mb_substr($value, -$visible);

        return $start . '****' . $end;
    }
}

namespace Mariusz\Logger;

/**
 * Safely serializes arbitrary log context values to JSON-encodable arrays.
 *
 * Handles: scalars, null, arrays (recursive), Throwable, objects with __toString,
 * objects with toArray/jsonSerialize, generic objects (public properties), resources.
 */
final class LogContextSerializer
{
    /**
     * Recursively serialize all values in a context array.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function serialize(array $context): array
    {
        foreach ($context as $key => $value) {
            $context[$key] = $this->serializeValue($value);
        }

        return $context;
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $this->serialize($value);
        }

        if ($value instanceof \Throwable) {
            return $this->serializeThrowable($value);
        }

        if (is_resource($value)) {
            return get_resource_type($value) . ' resource';
        }

        if (is_object($value)) {
            return $this->serializeObject($value);
        }

        return (string) $value;
    }

    /** @return array<string, mixed> */
    private function serializeThrowable(\Throwable $e): array
    {
        $data = [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => basename(dirname($e->getFile())) . '/' . basename($e->getFile()) . ':' . $e->getLine(),
        ];

        if ($e->getPrevious() !== null) {
            $data['previous'] = $this->serializeThrowable($e->getPrevious());
        }

        return $data;
    }

    private function serializeObject(object $value): mixed
    {
        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (method_exists($value, '__toString')) {
            return (string) $value;
        }

        $vars = get_object_vars($value);

        return $vars !== [] ? array_merge(['class' => $value::class], $vars) : $value::class;
    }
}

namespace Mariusz\Logger;

/**
 * Writes log messages to date-based files with automatic rotation.
 */
class LogFileManager
{
    private string $logDir;
    private int $maxFileSize;
    private int $maxFiles;
    private string $prefix;
    private string $suffix;
    private string $dateStructure;

    /**
     * @param string $logDir        Base log directory (e.g. ./logs).
     * @param int    $maxFileSize   Max file size in bytes before rotation (1MB by default).
     * @param int    $maxFiles      Max number of archive files to keep (5 by default).
     * @param string $prefix        Optional filename prefix, e.g. "app-" → app-2026-05-02.log
     * @param string $suffix        Optional filename suffix, e.g. "-prod" → 2026-05-02-prod.log
     * @param string $dateStructure Subdirectory date pattern (default: "Y/m" → 2026/05/).
     *                              Use "Y" for yearly, "Y/m/d" for daily dirs, "" for flat.
     */
    public function __construct(
        string $logDir,
        int $maxFileSize = 1048576,
        int $maxFiles = 5,
        string $prefix = '',
        string $suffix = '',
        string $dateStructure = 'Y/m',
    ) {
        $this->logDir        = rtrim($logDir, '/');
        $this->maxFileSize   = $maxFileSize;
        $this->maxFiles      = max(1, $maxFiles);
        $this->prefix        = $prefix;
        $this->suffix        = $suffix;
        $this->dateStructure = $dateStructure;
    }

    public function write(string $message): void
    {
        $path = $this->resolvePath();

        if ($this->needsRotation($path)) {
            $this->rotate($path);
        }

        $this->ensureDirectory($path);
        file_put_contents($path, $message, FILE_APPEND);
    }

    /**
     * Builds the current log file path based on dateStructure:
     *   "Y/m"   → logs/2026/05/2026-05-02.log  (default)
     *   "Y"     → logs/2026/2026-05-02.log
     *   "Y/m/d" → logs/2026/05/02/2026-05-02.log
     *   ""      → logs/2026-05-02.log            (flat)
     */
    private function resolvePath(): string
    {
        $subDir = $this->dateStructure !== '' ? '/' . date($this->dateStructure) : '';

        return sprintf('%s%s/%s%s%s.log',
            $this->logDir,
            $subDir,
            $this->prefix,
            date('Y-m-d'),
            $this->suffix,
        );
    }

    private function needsRotation(string $path): bool
    {
        return file_exists($path) && filesize($path) >= $this->maxFileSize;
    }

    private function ensureDirectory(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function rotate(string $path): void
    {
        clearstatcache(true, $path);

        for ($i = $this->maxFiles; $i > 0; $i--) {
            $source = $path . '.' . $i;
            if (file_exists($source) && $i < $this->maxFiles) {
                rename($source, $path . '.' . ($i + 1));
            }
        }

        if (file_exists($path)) {
            rename($path, $path . '.1');
        }
    }
}

namespace Mariusz\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Logs all messages to STDERR and delegates file writes to LogFileManager.
 */
class DualLogger extends AbstractLogger
{
    private ?LogFileManager $fileManager;
    private LogAnonymizer $anonymizer;
    private LogContextSerializer $serializer;
    private int $minLevelValue;
    private string $dateFormat;
    private ?\DateTimeZone $timezone;
    private bool $stderrEnabled;
    private bool $stderrSkipInTest;

    /** @var array<string, int> */
    private array $levels = [
        LogLevel::DEBUG     => 100,
        LogLevel::INFO      => 200,
        LogLevel::NOTICE    => 300,
        LogLevel::WARNING   => 400,
        LogLevel::ERROR     => 500,
        LogLevel::CRITICAL  => 600,
        LogLevel::ALERT     => 700,
        LogLevel::EMERGENCY => 800,
    ];

    /**
     * Quick factory: creates a DualLogger writing to $logDir with default settings.
     */
    public static function create(
        string $logDir,
        string $minLevel = LogLevel::WARNING,
        string $dateFormat = 'Y-m-d H:i:s',
        string $timezone = '',
        string $prefix = '',
        string $suffix = '',
        bool $stderrEnabled = true,
        bool $stderrSkipInTest = true,
    ): self {
        return new self(new LogFileManager($logDir, prefix: $prefix, suffix: $suffix), $minLevel, $dateFormat, $timezone, $stderrEnabled, $stderrSkipInTest);
    }

    /**
     * @param LogFileManager|null $fileManager      Optional log file manager.
     * @param string              $minLevel         Minimum PSR-3 level written to file (default: warning).
     * @param string              $dateFormat       Date format for log entries (default: Y-m-d H:i:s).
     * @param string              $timezone         Timezone for log timestamps (default: system timezone).
     * @param bool                $stderrEnabled    Whether to write to STDERR at all (default: true).
     * @param bool                $stderrSkipInTest Suppress STDERR when APP_ENV=test (default: true).
     */
    public function __construct(
        ?LogFileManager $fileManager = null,
        string $minLevel = LogLevel::WARNING,
        string $dateFormat = 'Y-m-d H:i:s',
        string $timezone = '',
        bool $stderrEnabled = true,
        bool $stderrSkipInTest = true,
    ) {
        $this->fileManager      = $fileManager;
        $this->anonymizer       = new LogAnonymizer();
        $this->serializer       = new LogContextSerializer();
        $this->minLevelValue    = $this->levels[$minLevel] ?? $this->levels[LogLevel::WARNING];
        $this->dateFormat       = $dateFormat;
        $this->timezone         = $timezone !== '' ? new \DateTimeZone($timezone) : null;
        $this->stderrEnabled    = $stderrEnabled;
        $this->stderrSkipInTest = $stderrSkipInTest;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $entry = $this->format($level, $message, $this->anonymizer->anonymize($this->serializer->serialize($context)));

        $this->writeToStderr($entry);

        if ($this->meetsMinLevel($level)) {
            $this->fileManager?->write($entry);
        }
    }

    private function format(string $level, string $message, array $context): string
    {
        return sprintf("[%s] [%s] [%s] %s %s\n",
            (new \DateTime('now', $this->timezone))->format($this->dateFormat),
            strtoupper($level),
            $this->resolveLocation(),
            $message,
            empty($context) ? '' : json_encode($context),
        );
    }

    private function resolveLocation(): string
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)[3] ?? [];

        return isset($caller['file'], $caller['line'])
            ? sprintf('%s/%s:%d', basename(dirname($caller['file'])), basename($caller['file']), $caller['line'])
            : 'unknown';
    }

    private function writeToStderr(string $entry): void
    {
        if (!$this->stderrEnabled || !defined('STDERR')) {
            return;
        }

        if ($this->stderrSkipInTest && ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV')) === 'test') {
            return;
        }

        fwrite(\STDERR, $entry);
    }

    private function meetsMinLevel(string $level): bool
    {
        return ($this->levels[$level] ?? 0) >= $this->minLevelValue;
    }
}
