<?php

declare(strict_types=1);

namespace Mariusz\Logger\Laravel;

use Illuminate\Support\ServiceProvider;
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;

final class LoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/php-logger.php', 'php-logger');

        $this->app->singleton(DualLogger::class, function ($app) {
            $cfg  = $app['config']['php-logger'];
            $file = $cfg['file'];

            return new DualLogger(
                new LogFileManager(
                    $cfg['log_dir'],
                    $file['max_file_size'],
                    $file['max_files'],
                    $file['prefix'],
                    $file['suffix'],
                    $file['date_structure'],
                ),
                $cfg['min_level'],
                $cfg['date_format'],
                $cfg['timezone'],
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/php-logger.php' => config_path('php-logger.php'),
        ], 'fast-php-logger-config');
    }
}
