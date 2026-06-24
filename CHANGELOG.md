# Changelog

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
