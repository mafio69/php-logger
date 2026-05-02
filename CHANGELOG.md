# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] — 2026-05-02

### Added
- `DualLogger` — PSR-3 compliant logger writing to STDERR and file simultaneously
- `LogFileManager` — date-based log files with automatic size rotation, configurable prefix/suffix/dateStructure
- `LogAnonymizer` — automatic masking of sensitive fields (email, token, password, pesel, iban, etc.)
- `DualLogger::create()` static factory for quick setup
- Laravel Service Provider with auto-discovery and publishable config
- Symfony Bundle with `PhpLoggerExtension` and `Configuration`
