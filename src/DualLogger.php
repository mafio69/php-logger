<?php

namespace Mariusz\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

/**
 * A logger that writes all logs to STDERR (screen)
 * and delegates logs from WARNING level upwards to a log file manager.
 */
class DualLogger extends AbstractLogger
{
    private ?LogFileManager $fileManager;
    private LogAnonymizer $anonymizer;

    /** @var array<string, int> PSR-3 compliant log level map. */
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
     * @param LogFileManager|null $fileManager Optional log file manager object.
     */
    public function __construct(?LogFileManager $fileManager = null)
    {
        $this->fileManager = $fileManager;
        $this->anonymizer  = new LogAnonymizer();
    }

    /**
     * @param $level
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $context = $this->anonymizer->anonymize($context);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? $trace[1] ?? [];
        $location = isset($caller['file'], $caller['line'])
            ? sprintf('%s/%s:%d', basename(dirname($caller['file'])), basename($caller['file']), $caller['line'])
            : 'unknown';

        $formattedMessage = sprintf("[%s] [%s] [%s] %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $location, $message, empty($context) ? '' : json_encode($context));

        // 1. Write to the screen (STDERR) only if the constant is defined (i.e., in CLI mode) and not in test environment.
        if (defined('STDERR') && ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV')) !== 'test') {
            fwrite(\STDERR, $formattedMessage);
        }

        // 2. If a file manager is available and the log level is high enough, delegate the write operation.
        if ($this->fileManager && ($this->levels[$level] ?? 0) >= $this->levels[LogLevel::WARNING]) {
            $this->fileManager->write($formattedMessage);
        }
    }
}
