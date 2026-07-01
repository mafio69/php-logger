<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests;

use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogAnonymizer;
use Mariusz\Logger\LogContextSerializer;
use Mariusz\Logger\LogFileManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class DualLoggerTest extends TestCase
{
    private MockObject|LogFileManager $fileManager;
    private MockObject|LogAnonymizer $anonymizer;
    private MockObject|LogContextSerializer $serializer;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(LogFileManager::class);
        $this->anonymizer = $this->createMock(LogAnonymizer::class);
        $this->serializer = $this->createMock(LogContextSerializer::class);

        // Default behavior for mocks to avoid setting it in every test
        $this->serializer->method('serialize')->willReturnArgument(0);
        $this->anonymizer->method('anonymize')->willReturnArgument(0);
    }

    private function createLogger(
        string $minLevel = LogLevel::WARNING,
        string $dateFormat = 'Y-m-d H:i:s',
        string $timezone = '',
        bool $stderrEnabled = false,
        bool $stderrSkipInTest = true
    ): DualLogger {
        return new DualLogger(
            $this->fileManager,
            $this->anonymizer,
            $this->serializer,
            new LoggerConfigDto($minLevel, $dateFormat, $timezone, $stderrEnabled, $stderrSkipInTest),
        );
    }

    public function testInfoDoesNotWriteToFile(): void
    {
        $this->fileManager->expects($this->never())->method('write');
        $logger = $this->createLogger();
        $logger->info('just info');
    }

    public function testWarningWritesToFile(): void
    {
        $this->fileManager->expects($this->once())->method('write')
            ->with($this->stringContains('[WARNING]'));
        $logger = $this->createLogger();
        $logger->warning('something wrong');
    }

    public function testErrorWritesToFile(): void
    {
        $this->fileManager->expects($this->once())->method('write');
        $logger = $this->createLogger();
        $logger->error('an error');
    }

    public function testContextIsSerialized(): void
    {
        $context = ['username' => 'jankowalski'];
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($context)
            ->willReturn($context); // Return the same context for simplicity

        $this->fileManager->expects($this->once())->method('write')
            ->with($this->stringContains(json_encode($context)));

        $logger = $this->createLogger();
        $logger->warning('msg', $context);
    }

    public function testSensitiveContextIsAnonymized(): void
    {
        $this->fileManager->expects($this->once())
            ->method('write')
            ->with($this->callback(function (string $entry): bool {
                $this->assertStringContainsString('auth', $entry);
                $this->assertStringContainsString('token', $entry);
                $this->assertStringNotContainsString('supersecret123', $entry);
                $this->assertStringContainsString('****', $entry);

                return true;
            }));

        // Test with real dependencies by passing null
        $logger = new DualLogger(
            $this->fileManager,
            null,
            null,
            new LoggerConfigDto(LogLevel::DEBUG),
        );

        $logger->warning('auth', ['token' => 'supersecret123']);
    }

    public function testLogMessageContainsTimestamp(): void
    {
        $this->fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/'));
        $logger = $this->createLogger();
        $logger->warning('ts check');
    }

    public function testLogMessageContainsCallerLocation(): void
    {
        $this->fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\w+\/\w+\.php:\d+\]/'));
        $logger = $this->createLogger();
        $logger->warning('location check');
    }

    public function testWorksWithoutFileManager(): void
    {
        $logger = new DualLogger(null, $this->anonymizer, $this->serializer);
        $logger->info('no file manager');
        $this->assertTrue(true); // Should not throw
    }

    public function testMinLevelDebugWritesInfoToFile(): void
    {
        $this->fileManager->expects($this->once())->method('write');
        $logger = $this->createLogger(LogLevel::DEBUG);
        $logger->info('should be written');
    }

    public function testMinLevelErrorSkipsWarning(): void
    {
        $this->fileManager->expects($this->never())->method('write');
        $logger = $this->createLogger(LogLevel::ERROR);
        $logger->warning('should be skipped');
    }

    public function testMinLevelErrorWritesError(): void
    {
        $this->fileManager->expects($this->once())->method('write');
        $logger = $this->createLogger(LogLevel::ERROR);
        $logger->error('should be written');
    }

    public function testDefaultMinLevelIsWarning(): void
    {
        $this->fileManager->expects($this->never())->method('write');
        $logger = $this->createLogger();
        $logger->notice('below warning — should not write');
    }

    public function testCustomDateFormat(): void
    {
        $this->fileManager->expects($this->once())->method('write')
            ->with($this->matchesRegularExpression('/\[\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}\]/'));
        $logger = $this->createLogger(LogLevel::WARNING, 'd.m.Y H:i');
        $logger->warning('custom format');
    }

    public function testCustomTimezoneIsApplied(): void
    {
        $captured = '';
        $this->fileManager->expects($this->once())->method('write')
            ->willReturnCallback(function (string $msg) use (&$captured) { $captured = $msg; });

        $logger = $this->createLogger(LogLevel::WARNING, 'Y-m-d H:i:s', 'UTC');
        $logger->warning('tz test');

        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $captured, $matches);
        $this->assertNotEmpty($matches[1]);
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $matches[1], new \DateTimeZone('UTC'));
        $this->assertInstanceOf(\DateTime::class, $dt);
    }

    public function testStaticCreateWorks(): void
    {
        $logger = DualLogger::create(sys_get_temp_dir() . '/log_create_test');
        $this->assertInstanceOf(DualLogger::class, $logger);
    }

    public function testUnknownMinLevelFallsBackToWarning(): void
    {
        $this->fileManager->expects($this->never())->method('write');
        $logger = $this->createLogger('nieistniejacy_level');
        $logger->notice('should be skipped');
    }

    public function testLogWithStringableMessage(): void
    {
        $this->fileManager->expects($this->once())->method('write')
            ->with($this->stringContains('logged object'));
        $logger = $this->createLogger(LogLevel::DEBUG);
        $logger->info(new class {
            public function __toString(): string
            {
                return 'logged object';
            }
        });
    }
}