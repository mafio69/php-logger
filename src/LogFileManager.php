<?php

namespace Mariusz\Logger;

/**
 * Manages the log file, including file rotation when the size limit is exceeded.
 * Responsible for the physical write to disk.
 */
class LogFileManager
{
    private string $logDir;
    private int $maxFileSize;
    private int $maxFiles;

    /**
     * @param string $logDir Base log directory (e.g. ./logs).
     * @param int $maxFileSize The maximum file size in bytes before rotation (1MB by default).
     * @param int $maxFiles The maximum number of archive files to keep (5 by default).
     */
    public function __construct(string $logDir, int $maxFileSize = 1048576, int $maxFiles = 5)
    {
        $this->logDir = rtrim($logDir, '/');
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = max(1, $maxFiles);
    }

    /**
     * Returns the current log file path: logs/{year}/{month}/{year-month-day}.log
     */
    private function currentPath(): string
    {
        return sprintf('%s/%s/%s/%s.log',
            $this->logDir,
            date('Y'),
            date('m'),
            date('Y-m-d')
        );
    }

    /**
     * Writes the message to the log file, handling rotation.
     */
    public function write(string $message): void
    {
        $path = $this->currentPath();

        if (file_exists($path) && filesize($path) >= $this->maxFileSize) {
            $this->rotate($path);
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $message, FILE_APPEND);
    }

    /**
     * Performs the log file rotation.
     */
    private function rotate(string $logFilePath): void
    {
        clearstatcache(true, $logFilePath);

        for ($i = $this->maxFiles; $i > 0; $i--) {
            $source = $logFilePath . '.' . $i;
            if (file_exists($source)) {
                $destination = $logFilePath . '.' . ($i + 1);
                if ($i < $this->maxFiles) {
                    rename($source, $destination);
                }
            }
        }

        if (file_exists($logFilePath)) {
            rename($logFilePath, $logFilePath . '.1');
        }
    }
}
