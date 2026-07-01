<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit\Symfony;

use Mariusz\Logger\DualLogger;
use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\Symfony\DependencyInjection\PhpLoggerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class LoggerExtensionTest extends TestCase
{
    public function testItRegistersDualLoggerInContainer(): void
    {
        $container = new ContainerBuilder();
        $extension = new PhpLoggerExtension();

        $config = [
            'log_dir' => '/tmp/logs',
            'min_level' => 'warning',
            'date_format' => 'Y-m-d H:i:s',
            'timezone' => 'Europe/Warsaw',
            'file' => [
                'max_file_size' => 1048576,
                'max_files' => 5,
                'prefix' => '',
                'suffix' => '',
                'date_structure' => 'Y/m',
            ],
            'stderr' => [
                'enabled' => true,
                'skip_in_test' => true,
            ],
        ];

        $extension->load([$config], $container);

        $container->compile();

        $logger = $container->get(DualLogger::class);

        $this->assertInstanceOf(DualLogger::class, $logger);
    }

    public function testItPassesConfigurationToLogger(): void
    {
        $container = new ContainerBuilder();
        $extension = new PhpLoggerExtension();

        $config = [
            'log_dir' => '/tmp/logs',
            'min_level' => 'debug',
            'date_format' => 'd.m.Y H:i',
            'timezone' => 'UTC',
            'file' => [
                'max_file_size' => 2097152,
                'max_files' => 10,
                'prefix' => 'app-',
                'suffix' => '-prod',
                'date_structure' => 'Y/m/d',
            ],
            'stderr' => [
                'enabled' => false,
                'skip_in_test' => false,
            ],
        ];

        $extension->load([$config], $container);
        $container->compile();

        $logger = $container->get(DualLogger::class);
        $dto = $logger->getConfig();

        $this->assertSame('debug', $dto->minLevel);
        $this->assertSame('d.m.Y H:i', $dto->dateFormat);
        $this->assertSame('UTC', $dto->timezone);
        $this->assertFalse($dto->stderrEnabled);
        $this->assertFalse($dto->stderrSkipInTest);
    }
}
