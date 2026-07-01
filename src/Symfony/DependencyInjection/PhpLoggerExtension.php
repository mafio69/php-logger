<?php

declare(strict_types=1);

namespace Mariusz\Logger\Symfony\DependencyInjection;

use Mariusz\Logger\Dto\LoggerConfigDto;
use Mariusz\Logger\DualLogger;
use Mariusz\Logger\LogFileManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class PhpLoggerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->register(LogFileManager::class, LogFileManager::class)
            ->setArguments([
                $config['log_dir'],
                $config['file']['max_file_size'],
                $config['file']['max_files'],
                $config['file']['prefix'],
                $config['file']['suffix'],
                $config['file']['date_structure'],
            ]);

        $container->register(LoggerConfigDto::class, LoggerConfigDto::class)
            ->setArguments([
                $config['min_level'],
                $config['date_format'],
                $config['timezone'],
                $config['stderr']['enabled'],
                $config['stderr']['skip_in_test'],
            ]);

        $container->register(DualLogger::class, DualLogger::class)
            ->setArguments([
                $container->getDefinition(LogFileManager::class),
                null,
                null,
                $container->getDefinition(LoggerConfigDto::class),
            ])
            ->setPublic(true);
    }
}
