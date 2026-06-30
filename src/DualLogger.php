<?php

declare(strict_types=1);

namespace Mariusz\Logger;

use DateTimeZone;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

class DualLogger extends AbstractLogger
{
    private LogFileManager|null $fileManager;
    private LogAnonymizer $anonymizer;
    private LogContextSerializer $serializer;
    private int $minLevelValue;
    private string $dateFormat;
    private ?DateTimeZone $timezone;
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
    private string $minLevel;

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
        return new self(
            new LogFileManager($logDir, prefix: $prefix, suffix: $suffix),
            null,
            null,
            $minLevel,
            $dateFormat,
            $timezone,
            $stderrEnabled,
            $stderrSkipInTest
        );
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function __construct(
        ?LogFileManager $fileManager = null,
        ?LogAnonymizer $anonymizer = null,
        ?LogContextSerializer $serializer = null,
        string $minLevel = LogLevel::WARNING,
        string $dateFormat = 'Y-m-d H:i:s',
        string $timezone = '',
        bool $stderrEnabled = true,
        bool $stderrSkipInTest = true,
    ) {
        $this->fileManager      = $fileManager;
        $this->anonymizer       = $anonymizer ?? new LogAnonymizer();
        $this->serializer       = $serializer ?? new LogContextSerializer();
        $this->minLevelValue    = $this->levels[$minLevel] ?? $this->levels[LogLevel::WARNING];
        $this->dateFormat       = $dateFormat;
        $this->timezone         = $timezone !== '' ? new DateTimeZone($timezone) : null;
        $this->stderrEnabled    = $stderrEnabled;
        $this->stderrSkipInTest = $stderrSkipInTest;
        $this->minLevel         = $minLevel;
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
        return sprintf(
            "[%s] [%s] [%s] %s %s\n",
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