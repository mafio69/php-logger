<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\LogFileManager;
use PHPUnit\Framework\TestCase;

final class DualLoggerConfigTest extends TestCase
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

        $this->assertInstanceOf(DualLogger::class, $logger);
    }
}