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
    /** @var resource|null */
    private static $filterHandle = null;

    public static function start(): void
    {
        if (!self::$registered) {
            stream_filter_register('stderr_capture', self::class);
            self::$registered = true;
        }

        self::$captured = '';
        self::$filterHandle = stream_filter_append(\STDERR, 'stderr_capture');
    }

    public static function stop(): string
    {
        if (self::$filterHandle !== null) {
            stream_filter_remove(self::$filterHandle);
            self::$filterHandle = null;
        }

        $captured = self::$captured;
        self::$captured = '';
        return $captured;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = \stream_bucket_make_writeable($in)) {
            self::$captured .= $bucket->data;
            $consumed += (int) $bucket->datalen;
        }

        return \PSFS_PASS_ON;
    }
}