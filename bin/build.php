#!/usr/bin/env php
<?php

/**
 * Builds dist/fast-php-logger.php — a single-file, zero-dependency bundle
 * that can be used without Composer via a single require_once.
 *
 * Usage: php bin/build.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$out  = $root . '/dist/fast-php-logger.php';

// Order matters: dependencies first
$files = [
    $root . '/vendor/psr/log/src/LogLevel.php',
    $root . '/vendor/psr/log/src/LoggerInterface.php',
    $root . '/vendor/psr/log/src/LoggerTrait.php',
    $root . '/vendor/psr/log/src/AbstractLogger.php',
    $root . '/src/LogAnonymizer.php',
    $root . '/src/LogContextSerializer.php',
    $root . '/src/LogFileManager.php',
    $root . '/src/DualLogger.php',
];

$parts = [];

foreach ($files as $file) {
    $src = file_get_contents($file);

    // Strip opening <?php tag and declare(strict_types=1)
    $src = preg_replace('/^<\?php\s*/s', '', $src);
    $src = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/s', '', $src);

    $parts[] = trim($src);
}

if (!is_dir(dirname($out))) {
    mkdir(dirname($out), 0755, true);
}

$version = trim(shell_exec('git -C ' . escapeshellarg($root) . ' describe --tags --always 2>/dev/null') ?: 'dev');
$date    = date('Y-m-d');

$header = <<<PHP
<?php
/**
 * fast-php-logger — single-file build ({$version}) — {$date}
 * https://github.com/mafio69/php-logger
 *
 * Usage:
 *   require_once 'fast-php-logger.php';
 *   \$logger = \Mariusz\Logger\DualLogger::create('./logs');
 */

declare(strict_types=1);

PHP;

file_put_contents($out, $header . implode("\n\n", $parts) . "\n");

echo "Built: {$out}\n";
