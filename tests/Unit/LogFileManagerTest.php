<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\LogFileManager;
use PHPUnit\Framework\TestCase;

final class LogFileManagerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/mcp_log_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Remove all files and subdirectories created during the test
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }

    public function testWriteCreatesDateBasedFile(): void
    {
        $manager = new LogFileManager($this->tmpDir);
        $manager->write('hello log');

        $expected = sprintf('%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($expected);
        $this->assertStringContainsString('hello log', file_get_contents($expected));
    }

    public function testWriteAppendsToExistingFile(): void
    {
        $manager = new LogFileManager($this->tmpDir);
        $manager->write("line1\n");
        $manager->write("line2\n");

        $path = sprintf('%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $content = file_get_contents($path);
        $this->assertStringContainsString('line1', $content);
        $this->assertStringContainsString('line2', $content);
    }

    public function testRotationCreatesArchiveWhenSizeExceeded(): void
    {
        $manager = new LogFileManager($this->tmpDir, maxFileSize: 10);

        $manager->write('12345678901'); // 11 bytes — triggers rotation on next write
        $manager->write('new entry');

        $path = sprintf('%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($path . '.1');
        $this->assertStringContainsString('new entry', file_get_contents($path));
    }

    public function testPrefixIsIncludedInFilename(): void
    {
        $manager = new LogFileManager($this->tmpDir, prefix: 'app-');
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/app-%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($expected);
    }

    public function testSuffixIsIncludedInFilename(): void
    {
        $manager = new LogFileManager($this->tmpDir, suffix: '-prod');
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/%s-prod.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($expected);
    }

    public function testPrefixAndSuffixCombined(): void
    {
        $manager = new LogFileManager($this->tmpDir, prefix: 'app-', suffix: '-prod');
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/app-%s-prod.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($expected);
    }

    public function testNoPrefixOrSuffixUsesDefaultFilename(): void
    {
        $manager = new LogFileManager($this->tmpDir);
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );

        $this->assertFileExists($expected);
    }

    public function testDateStructureYearOnly(): void
    {
        $manager = new LogFileManager($this->tmpDir, dateStructure: 'Y');
        $manager->write('test');

        $expected = sprintf('%s/%s/%s.log', $this->tmpDir, date('Y'), date('Y-m-d'));
        $this->assertFileExists($expected);
    }

    public function testDateStructureFlat(): void
    {
        $manager = new LogFileManager($this->tmpDir, dateStructure: '');
        $manager->write('test');

        $expected = sprintf('%s/%s.log', $this->tmpDir, date('Y-m-d'));
        $this->assertFileExists($expected);
    }

    public function testDateStructureDailyDir(): void
    {
        $manager = new LogFileManager($this->tmpDir, dateStructure: 'Y/m/d');
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('d'), date('Y-m-d')
        );
        $this->assertFileExists($expected);
    }

    public function testDefaultDateStructureIsYearMonth(): void
    {
        $manager = new LogFileManager($this->tmpDir);
        $manager->write('test');

        $expected = sprintf('%s/%s/%s/%s.log',
            $this->tmpDir, date('Y'), date('m'), date('Y-m-d')
        );
        $this->assertFileExists($expected);
    }
}
