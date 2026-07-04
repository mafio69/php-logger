# Roadmap

## 1. Log levels per output
- Separate `minLevel` settings for STDERR and file output.
- Allow different thresholds for different destinations.
- Example: `stderr = warn+`, `file = info+`.

## 2. Multiple file handlers
- Support multiple file handlers at the same time.
- Example: one file for `error` logs, another for `debug` logs.
- Allow splitting logs by level or category.

## 3. Structured logging
- Add JSON output format.
- Keep log context outside the message body where possible.
- Make output compatible with log aggregators like ELK and Splunk.

## 4. Custom formatters
- Replace the fixed format with pluggable formatters.
- Allow users to define their own formatting logic.
- Support plain text, JSON, and custom output styles.

## 5. Async / buffered writes
- Add buffered writes for higher throughput.
- Reduce synchronous I/O overhead.
- Make flushing configurable for safety vs performance.

## 6. Handler registry
- Create a registry for custom handlers.
- Make it easy to add handlers for database, API, or other targets.
- Define a simple handler interface for extensions.

## 7. Context inheritance
- Support global context shared across logs.
- Include values like request ID and user ID automatically.
- Allow per-request context overrides.

## 8. Log channels
- Introduce log channels such as `database`, `auth`, and `payments`.
- Allow per-channel settings and handlers.
- Support separate levels and outputs per channel.

## 9. Performance metrics
- Add timers for measuring operation duration.
- Log elapsed time in a structured way.
- Support start/stop timing helpers and scoped timers.

## 10. Documentation
- Add API documentation.
- Create flow diagrams for log processing.
- Write troubleshooting guides for framework integrations.
- Include examples for common setups and edge cases.