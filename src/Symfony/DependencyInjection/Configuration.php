<?php

declare(strict_types=1);

namespace Mariusz\Logger\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('php_logger');
        $root = $tree->getRootNode();

        $root->children()
            ->scalarNode('log_dir')->defaultValue('%kernel.logs_dir%')->end()
            ->scalarNode('min_level')->defaultValue('warning')->end()
            ->scalarNode('date_format')->defaultValue('Y-m-d H:i:s')->end()
            ->scalarNode('timezone')->defaultValue('')->end()
            ->arrayNode('file')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('max_file_size')->defaultValue(1048576)->end()
                    ->integerNode('max_files')->defaultValue(5)->end()
                    ->scalarNode('prefix')->defaultValue('')->end()
                    ->scalarNode('suffix')->defaultValue('')->end()
                    ->scalarNode('date_structure')->defaultValue('Y/m')->end()
                ->end()
            ->end()
            ->arrayNode('stderr')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')->defaultTrue()->end()
                    ->booleanNode('skip_in_test')->defaultTrue()->end()
                ->end()
            ->end()
        ->end();

        return $tree;
    }
}
