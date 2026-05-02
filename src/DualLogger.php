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
