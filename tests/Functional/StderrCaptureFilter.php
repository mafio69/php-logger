<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Functional;

/**
 * Stream filter that captures writes to STDERR without passing them
 * to the actual stderr descriptor. Used by LoggerEndToEndTest to
 * assert what DualLogger wrote to STDERR without polluting test output.
 */
final class StderrCaptureFilter extends \php_user_filter
{
    private static string $captured = '';
    private static bool $registered = false;

    public static function start(): void
    {
        if (!self::$registered) {
            stream_filter_register('stderr_capture', self::class);
            self::$registered = true;
        }

        self::$captured = '';
        stream_filter_append(\STDERR, 'stderr_capture');
    }

    public static function stop(): string
    {
        $captured = self::$captured;
        self::$captured = '';
        return $captured;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = \stream_bucket_make_writeable($in)) {
            self::$captured .= $bucket->data;
            $consumed += $bucket->datalen;
        }

        return \PSFS_PASS_ON;
    }
}