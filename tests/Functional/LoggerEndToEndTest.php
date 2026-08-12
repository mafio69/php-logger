<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Functional;

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\LogFileManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * End-to-end tests that verify the full logging pipeline:
 * DualLogger → format → anonymize → serialize → LogFileManager → file
 *
 * These tests use real file I/O (not mocks) to verify actual behavior.
 */
final class LoggerEndToEndTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/logger_e2e_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        @rmdir($this->tmpDir);
    }

    /**
     * Helper: resolve expected log file path based on dateStructure.
     */
    private function resolveLogPath(string $dateStructure = 'Y/m'): string
    {
        $subDir = $dateStructure !== '' ? '/' . date($dateStructure) : '';
        return sprintf('%s%s/%s.log', $this->tmpDir, $subDir, date('Y-m-d'));
    }

    public function testLogWritesFormattedMessageToFile(): void
    {
        $logger = DualLogger::create(
            $this->tmpDir,
            minLevel: LogLevel::DEBUG,
            stderrEnabled: false
        );

        $logger->warning('Hello world');

        $path = $this->resolveLogPath();
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \[WARNING\] \[\w+\/\w+\.php:\d+\] Hello world\s*\n$/m',
            $content
        );
    }

    public function testMultipleLevelsRespectMinLevel(): void
    {
        $logger = DualLogger::create(
            $this->tmpDir,
            minLevel: LogLevel::WARNING,
            stderrEnabled: false
        );

        $logger->debug('debug message');
        $logger->info('info message');
        $logger->warning('warning message');
        $logger->error('error message');
        $logger->critical('critical message');

        $path = $this->resolveLogPath();
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('warning message', $content);
        $this->assertStringContainsString('error message', $content);
        $this->assertStringContainsString('critical message', $content);
        $this->assertStringNotContainsString('debug message', $content);
        $this->assertStringNotContainsString('info message', $content);
    }

    public function testAnonymizationAppearsInFileContent(): void
    {
        $logger = DualLogger::create(
            $this->tmpDir,
            minLevel: LogLevel::DEBUG,
            stderrEnabled: false
        );

        $logger->warning('Login attempt', [
            'token' => 'supersecret123',
            'email' => 'jan@example.com',
            'username' => 'visible',
        ]);

        $path = $this->resolveLogPath();
        $content = file_get_contents($path);

        $this->assertStringContainsString('****', $content);
        $this->assertStringNotContainsString('supersecret123', $content);
        $this->assertStringNotContainsString('jan@example.com', $content);
        $this->assertStringContainsString('"username":"visible"', $content);
    }

    public function testContextSerializationInFile(): void
    {
        $logger = DualLogger::create(
            $this->tmpDir,
            minLevel: LogLevel::DEBUG,
            stderrEnabled: false
        );

        $exception = new \RuntimeException('boom', 42);
        $logger->error('Exception occurred', ['error' => $exception]);

        $path = $this->resolveLogPath();
        $content = file_get_contents($path);

        $this->assertStringContainsString('"class":"RuntimeException"', $content);
        $this->assertStringContainsString('"message":"boom"', $content);
        $this->assertStringContainsString('"code":42', $content);
    }

    public function testRotationHappensThroughFullPipeline(): void
    {
        $logger = new DualLogger(
            new LogFileManager($this->tmpDir, maxFileSize: 20, maxFiles: 3),
            null,
            null,
            new LoggerConfigDto(
                minLevel: LogLevel::DEBUG,
                stderrEnabled: false
            )
        );

        // Each entry is >20 bytes, should trigger rotation
        $logger->warning(str_repeat('x', 30));
        $logger->warning(str_repeat('y', 30));
        $logger->warning(str_repeat('z', 30));

        $path = $this->resolveLogPath();
        $this->assertFileExists($path);
        $this->assertFileExists($path . '.1');
        $this->assertFileExists($path . '.2');

        // Active file should have the latest entry
        $content = file_get_contents($path);
        $this->assertStringContainsString(str_repeat('z', 30), $content);
    }

    public function testDateStructurePathMatchesConfig(): void
    {
        $logger = new DualLogger(
            new LogFileManager($this->tmpDir, dateStructure: 'Y/m/d'),
            null,
            null,
            new LoggerConfigDto(
                minLevel: LogLevel::DEBUG,
                stderrEnabled: false
            )
        );

        $logger->info('test message');

        $expectedPath = sprintf(
            '%s/%s/%s.log',
            $this->tmpDir,
            date('Y/m/d'),
            date('Y-m-d')
        );

        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('test message', file_get_contents($expectedPath));
    }

    public function testStderrReceivesEntry(): void
    {
        $logger = DualLogger::create(
            $this->tmpDir,
            minLevel: LogLevel::DEBUG,
            stderrEnabled: true,
            stderrSkipInTest: false // Override APP_ENV=test suppression
        );

        try {
            StderrCaptureFilter::start();
            $logger->error('stderr target');
            $captured = StderrCaptureFilter::stop();

            $this->assertStringContainsString('stderr target', $captured);
            $this->assertStringContainsString('[ERROR]', $captured);
        } finally {
            // Ensure filter is always removed even if assertion fails
            StderrCaptureFilter::stop();
        }
    }
}
