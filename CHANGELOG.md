# Changelog

All notable changes to `laravel-parallel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-11-10

### Added
- **Laravel Horizon Integration**: Complete integration with Laravel Horizon for monitoring parallel tasks
- Automatic metrics recording with start/completion timestamps, execution times, and success/failure status
- Four REST API endpoints for real-time metrics:
  - `/horizon/api/parallel/stats` - Overall statistics and performance metrics
  - `/horizon/api/parallel/workload` - Task workload breakdown by task type
  - `/horizon/api/parallel/jobs/recent` - Recent job execution history
  - `/horizon/api/parallel/workers` - Active worker process monitoring
- Event-driven metrics collection listening to TaskStarted, TaskCompleted, and TaskFailed events
- Automatic Horizon dashboard tagging (`parallel`, `parallel:{TaskName}`, `pool:{PoolName}`)
- Redis-based metrics storage with configurable connection support
- Configuration options for Horizon integration:
  - `PARALLEL_HORIZON_ENABLED` (default: true) - Toggle Horizon metrics recording
  - `PARALLEL_HORIZON_REDIS_CONNECTION` (default: "default") - Specify Redis connection
- `ParallelHorizonServiceProvider` for automatic registration when Horizon is installed
- `HorizonMetricsBridge` for seamless metrics synchronization
- `ParallelMetricsController` for API endpoint handling

### Documentation
- Added comprehensive Horizon integration guide at `docs/horizon-integration.md`
- Added "Integrations" section to main README.md
- Included setup instructions, configuration options, and dashboard usage examples
- Documented minimal performance overhead (less than 0.5ms per task)
- Added troubleshooting guide for common Horizon integration scenarios

## [1.0.0] - 2025-11-08

### Added
- Initial stable release of Laravel Parallel package
- True parallel processing for Laravel applications with real multi-process execution
- Built on amphp/parallel for battle-tested, production-ready performance
- Automatic CPU core detection and optimization for parallel task distribution
- Fluent, Laravel-style API with method chaining (`Parallel::workers(4)->timeout(30)->run()`)
- Comprehensive result handling with rich metrics and filtering capabilities
- Event-driven task lifecycle monitoring (TaskStarted, TaskCompleted, TaskFailed)
- PHPStan Level 9 type safety with full type coverage
- Full Laravel Octane and Swoole compatibility with non-singleton bindings
- Configurable worker pools with timeout and resource management
- Parallel map operations for collection processing
- Detailed execution metrics (success rate, execution time, task counts)
- Graceful error handling with per-task exception capture
- Auto-chunking for large task batches to optimize performance
- Comprehensive logging with configurable channels and levels
- Production-ready with 83.5% test coverage (251 tests, 672 assertions)

### Features
- **ParallelManager**: Core orchestrator for parallel task execution
- **Parallel Facade**: Static access following Laravel conventions
- **Task Abstraction**: Serializable closure and custom task support
- **Worker Pool**: Automatic worker lifecycle and resource management
- **Result Collection**: Laravel collection-style result manipulation
- **Execution Metrics**: Performance tracking and success rate monitoring
- **Event System**: Lifecycle hooks for observability and monitoring
- **Configuration**: Publishable config with environment variable support

### Requirements
- PHP 8.2 or higher
- Laravel 11.0 or 12.0
- amphp/parallel ^2.3
- laravel/serializable-closure ^2.0

### Documentation
- Comprehensive README with quick start guide
- Advanced usage examples and real-world scenarios
- Architecture documentation with design patterns
- Performance guidelines and best practices
- FAQ section covering common use cases
- Contributing guidelines for community contributions

[Unreleased]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/laravel-parallel/laravel-parallel/releases/tag/v1.0.0
