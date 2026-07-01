<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\LogFileManager;
use Mariusz\Logger\Laravel\LoggerServiceProvider;
use PHPUnit\Framework\TestCase;

final class LaravelLoggerConfigTest extends TestCase
{
    public function testItAcceptsConfigDto(): void
    {
        $logger = new DualLogger(
            new LogFileManager(sys_get_temp_dir()),
            null,
            null,
            new LoggerConfigDto(
                minLevel: 'warning',
                dateFormat: 'Y-m-d H:i:s',
                timezone: 'UTC',
                stderrEnabled: true,
                stderrSkipInTest: false,
            ),
        );

        $this->assertSame('warning', $logger->getConfig()->minLevel);
    }

    public function testItStoresConfigDto(): void
    {
        $config = new LoggerConfigDto(
            minLevel: 'warning',
            dateFormat: 'Y-m-d H:i:s',
            timezone: 'UTC',
            stderrEnabled: true,
            stderrSkipInTest: false,
        );

        $this->assertSame('warning', $config->minLevel);
        $this->assertSame('Y-m-d H:i:s', $config->dateFormat);
        $this->assertSame('UTC', $config->timezone);
        $this->assertTrue($config->stderrEnabled);
        $this->assertFalse($config->stderrSkipInTest);
    }
}