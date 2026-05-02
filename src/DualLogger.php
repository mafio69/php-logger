<?php

declare(strict_types=1);

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
    private int $minLevelValue;
    private string $dateFormat;
    private ?\DateTimeZone $timezone;

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
    ): self {
        return new self(new LogFileManager($logDir), $minLevel, $dateFormat, $timezone);
    }

    /**
     * @param LogFileManager|null $fileManager Optional log file manager.
     * @param string              $minLevel    Minimum PSR-3 level written to file (default: warning).
     * @param string              $dateFormat  Date format for log entries (default: Y-m-d H:i:s).
     * @param string              $timezone    Timezone for log timestamps (default: system timezone).
     */
    public function __construct(
        ?LogFileManager $fileManager = null,
        string $minLevel = LogLevel::WARNING,
        string $dateFormat = 'Y-m-d H:i:s',
        string $timezone = '',
    ) {
        $this->fileManager   = $fileManager;
        $this->anonymizer    = new LogAnonymizer();
        $this->minLevelValue = $this->levels[$minLevel] ?? $this->levels[LogLevel::WARNING];
        $this->dateFormat    = $dateFormat;
        $this->timezone      = $timezone !== '' ? new \DateTimeZone($timezone) : null;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $entry = $this->format($level, $message, $this->anonymizer->anonymize($context));

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
        if (defined('STDERR') && ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV')) !== 'test') {
            fwrite(\STDERR, $entry);
        }
    }

    private function meetsMinLevel(string $level): bool
    {
        return ($this->levels[$level] ?? 0) >= $this->minLevelValue;
    }
}
