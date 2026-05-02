<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies that dist/fast-php-logger.php (the no-Composer single-file build)
 * is present, syntactically valid, and functionally correct.
 *
 * Run `php bin/build.php` before this test if the file is missing.
 */
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

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }

    public function testDistFileLogsToFile(): void
    {
        $this->assertFileExists($this->distFile);

        $logDir  = sys_get_temp_dir() . '/fast-php-logger-build-test-' . uniqid();
        $tmpScript = tempnam(sys_get_temp_dir(), 'build-test-') . '.php';

        file_put_contents($tmpScript, '<?php' . "\n"
            . "require " . var_export($this->distFile, true) . ";\n"
            . "\$logger = \\Mariusz\\Logger\\DualLogger::create(" . var_export($logDir, true) . ", stderrEnabled: false);\n"
            . "\$logger->warning('build-test-message', ['token' => 'secret123']);\n"
        );

        exec('php ' . escapeshellarg($tmpScript) . ' 2>&1', $output, $exitCode);
        unlink($tmpScript);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($logDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.log')) {
                $files[] = $file->getPathname();
            }
        }
        $this->assertNotEmpty($files, 'No log file was created');

        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('[WARNING]', $content);
        $this->assertStringContainsString('build-test-message', $content);
        $this->assertStringContainsString('****', $content);
        $this->assertStringNotContainsString('secret123', $content);

        // Cleanup
        array_map('unlink', glob($logDir . '/**/*.log') ?: glob($logDir . '/*.log') ?: []);
        foreach (glob($logDir . '/**') ?: [] as $dir) {
            @rmdir($dir);
        }
        @rmdir($logDir);
    }
}
