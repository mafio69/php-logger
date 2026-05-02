# Changelog

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
