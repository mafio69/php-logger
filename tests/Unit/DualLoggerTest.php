<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class DualLoggerTest extends TestCase
{
    public function testInfoDoesNotWriteToFile(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->never())->method('write');

        $logger = new DualLogger($fileManager);
        $logger->info('just info');
    }

    public function testWarningWritesToFile(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->stringContains('[WARNING]'));

        $logger = new DualLogger($fileManager);
        $logger->warning('something wrong');
    }

    public function testErrorWritesToFile(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write');

        $logger = new DualLogger($fileManager);
        $logger->error('an error');
    }

    public function testContextIsSerialized(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->stringContains('"username":"jankowalski"'));

        $logger = new DualLogger($fileManager);
        $logger->warning('msg', ['username' => 'jankowalski']);
    }

    public function testSensitiveContextIsAnonymized(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->logicalAnd(
                $this->stringContains('****'),
                $this->logicalNot($this->stringContains('supersecret123'))
            ));

        $logger = new DualLogger($fileManager);
        $logger->warning('auth', ['token' => 'supersecret123']);
    }

    public function testLogMessageContainsTimestamp(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/'));

        $logger = new DualLogger($fileManager);
        $logger->warning('ts check');
    }

    public function testLogMessageContainsCallerLocation(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\w+\/\w+\.php:\d+\]/'));

        $logger = new DualLogger($fileManager);
        $logger->warning('location check');
    }

    public function testWorksWithoutFileManager(): void
    {
        $logger = new DualLogger();
        // Should not throw
        $logger->info('no file manager');
        $this->assertTrue(true);
    }

    public function testMinLevelDebugWritesInfoToFile(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write');

        $logger = new DualLogger($fileManager, minLevel: LogLevel::DEBUG);
        $logger->info('should be written');
    }

    public function testMinLevelErrorSkipsWarning(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->never())->method('write');

        $logger = new DualLogger($fileManager, minLevel: LogLevel::ERROR);
        $logger->warning('should be skipped');
    }

    public function testMinLevelErrorWritesError(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write');

        $logger = new DualLogger($fileManager, minLevel: LogLevel::ERROR);
        $logger->error('should be written');
    }

    public function testDefaultMinLevelIsWarning(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->never())->method('write');

        $logger = new DualLogger($fileManager);
        $logger->notice('below warning — should not write');
    }

    public function testCustomDateFormat(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}\]/'));

        $logger = new DualLogger($fileManager, dateFormat: 'd.m.Y H:i');
        $logger->warning('custom format');
    }

    public function testCustomTimezoneIsApplied(): void
    {
        $fileManager = $this->createMock(LogFileManager::class);
        $captured = '';
        $fileManager->expects($this->once())->method('write')
            ->willReturnCallback(function (string $msg) use (&$captured) { $captured = $msg; });

        $logger = new DualLogger($fileManager, timezone: 'UTC');
        $logger->warning('tz test');

        // Extract timestamp from log entry and verify it's a valid datetime
        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $captured, $matches);
        $this->assertNotEmpty($matches[1]);
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $matches[1], new \DateTimeZone('UTC'));
        $this->assertInstanceOf(\DateTime::class, $dt);
    }
}
