# Changelog

## [1.1.0] — 2026-10-25

### Added
- LoggerConfigDto — centralized configuration DTO for DualLogger
- PSR-3 level validation throwing InvalidArgumentException on invalid log level
- Exclusive file locking (`LOCK_EX`) in LogFileManager to prevent race conditions during concurrent writes
- Recursive array branch masking under sensitive keys in LogAnonymizer
- Comprehensive integration tests for Laravel ServiceProvider and Symfony DI Extension
- Functional and End-to-End test suites (66 tests, 130 assertions)
- Static analysis with PHPStan (level 5) and code style enforcement with PHP-CS-Fixer (PSR-12)
- Automated GitHub Release workflow with standalone `dist/fast-php-logger.php` artifact
- `.editorconfig` and `.gitattributes` for optimized package distribution

### Changed
- DualLogger refactored to use LoggerConfigDto
- DateTime handling migrated to DateTimeImmutable in DualLogger formatting
- Full codebase formatted to strict PSR-12 standard

## [1.0.1] — 2026-06-24

### Added
- LogContextSerializer — safe serialization of objects, exceptions, and resources in log context
- AIREADME.md — machine-readable project summary for AI assistants

### Fixed
- LogAnonymizer TypeError on integer array keys

### Changed
- Refactored LogFileManager for better path resolution

## [0.9.1] — 2026-05-02

### Added
- Single-file build (`dist/fast-php-logger.php`) for use without Composer
- `bin/build.php` — script to generate the single-file bundle
- `stderrEnabled` and `stderrSkipInTest` parameters in `DualLogger` constructor and `create()`
- `prefix` and `suffix` parameters in `DualLogger::create()`
- `stderr` config section in Laravel and Symfony integrations
- `BuildTest` — integration test verifying the single-file build

### Changed
- Improved README: Without Composer section, Quick start with code→output pairs, full configuration examples
