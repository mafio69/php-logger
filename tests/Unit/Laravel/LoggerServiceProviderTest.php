<?php

declare(strict_types=1);

namespace {
    // Stub for Laravel helper function
    if (!function_exists('storage_path')) {
        function storage_path($path = '') {
            return '/tmp/storage' . ($path ? '/' . $path : '');
        }
    }
}

namespace Mariusz\Logger\Tests\Unit\Laravel {

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\Laravel\LoggerServiceProvider;
use PHPUnit\Framework\TestCase;

final class LoggerServiceProviderTest extends TestCase
{
    public function testItRegistersDualLoggerInContainer(): void
    {
        $container = new Container();
        
        $config = new Repository([
            'php-logger' => [
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
            ],
        ]);

        $container->instance('config', $config);
        
        $provider = new LoggerServiceProvider($container);
        $provider->register();

        $logger = $container->make(DualLogger::class);

        $this->assertInstanceOf(DualLogger::class, $logger);
    }

    public function testItPassesConfigurationToLogger(): void
    {
        $container = new Container();
        
        $config = new Repository([
            'php-logger' => [
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
            ],
        ]);

        $container->instance('config', $config);
        
        $provider = new LoggerServiceProvider($container);
        $provider->register();

        $logger = $container->make(DualLogger::class);
        $dto = $logger->getConfig();

        $this->assertSame('debug', $dto->minLevel);
        $this->assertSame('d.m.Y H:i', $dto->dateFormat);
        $this->assertSame('UTC', $dto->timezone);
        $this->assertFalse($dto->stderrEnabled);
        $this->assertFalse($dto->stderrSkipInTest);
    }
}

} // namespace Mariusz\Logger\Tests\Unit\Laravel
