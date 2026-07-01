<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BuildTest extends TestCase
{
    private string $distFile;

    protected function setUp(): void
    {
        $this->distFile = dirname(__DIR__, 2) . '/dist/fast-php-logger.php';
    }

    public function testDistFileExists(): void
    {
        $this->assertFileExists(
            $this->distFile,
            'dist/fast-php-logger.php not found — run: php bin/build.php'
        );
    }

    public function testDistFileHasNoSyntaxErrors(): void
    {
        $this->assertFileExists($this->distFile);

        exec('php -l ' . escapeshellarg($this->distFile) . ' 2>&1', $output, $exitCode);
        $result = implode("\n", $output);

        $this->assertSame(0, $exitCode, $result);
        $this->assertStringContainsString('No syntax errors detected', $result);
    }


}