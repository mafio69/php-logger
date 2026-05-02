<?php

declare(strict_types=1);

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
