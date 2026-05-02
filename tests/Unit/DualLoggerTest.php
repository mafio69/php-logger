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
}
