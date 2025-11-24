# Changelog

All notable changes to `laravel-parallel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2025-01-24

### Added
- **Performance Benchmarking Suite**: Comprehensive benchmarking system for measuring parallel processing performance
  - Four built-in benchmark scenarios:
    - `cpu-bound` - CPU-intensive tasks (prime numbers, Fibonacci, hashing)
    - `io-bound` - I/O-intensive tasks (file operations, network simulation)
    - `mixed` - Mixed workload combining CPU and I/O operations
    - `memory` - Memory-intensive tasks (large array/string operations)
  - `BenchmarkRunner` orchestrator for executing benchmark scenarios
  - `BenchmarkResult` immutable value object with formatted metrics
  - `BenchmarkScenario` contract for creating custom benchmarks
  - `php artisan parallel:benchmark` command with options:
    - `--workers` - Specify worker count (default: auto-detect)
    - `--iterations` - Number of tasks per scenario (default: 100)
    - `--export` - Export results (json, csv, markdown)
  - Comprehensive performance metrics:
    - Total execution time
    - Average time per task
    - Throughput (tasks/second)
    - Peak memory usage
  - Configuration options in `config/parallel.php`:
    - `PARALLEL_BENCHMARKING_ENABLED` - Enable/disable benchmarking (default: true)
    - `PARALLEL_BENCHMARK_ITERATIONS` - Default iterations (default: 100)
    - `PARALLEL_BENCHMARK_WORKERS` - Default workers (default: null/auto-detect)
  - Support for custom benchmark scenarios with extensible architecture
  - Export capabilities for CI/CD integration and performance tracking

### Documentation
- Added comprehensive benchmarking guide at `docs/benchmarking.md`
  - Quick start guide and usage examples
  - Detailed explanation of all benchmark scenarios
  - Metrics interpretation and performance analysis
  - Custom scenario creation tutorial
  - CI/CD integration examples
  - Troubleshooting and performance tips

### Testing
- Added comprehensive test suite for benchmarking system:
  - Unit tests for `BenchmarkResult` value object (10 tests)
  - Unit tests for `BenchmarkRunner` orchestrator (8 tests)
  - Unit tests for all four scenario implementations (30+ tests)
  - Feature tests for end-to-end benchmarking (12 tests)
  - Command tests for `ParallelBenchmarkCommand` (14 tests)
  - Total: 74 new tests for benchmarking functionality

### Quality Assurance
- **PHPStan Level 9 Verification**: Confirmed package already compliant with PHPStan Level 9 (maximum strictness)
  - Configuration uses `level: max` in `phpstan.neon.dist`
  - All 32 source files pass strictest analysis with 0 errors
  - Strict type safety with `declare(strict_types=1)` throughout codebase
  - Full return type declarations and parameter type hints
  - Readonly properties and PHP 8.2+ features utilized
- **Test Coverage Analysis**: Comprehensive coverage audit performed
  - Current coverage: **71.2%** (325 tests passing, 61 skipped)
  - Previous documentation indicated 77.5% (now corrected)
  - Added 7 new test cases for `CpuDetector` edge scenarios
  - Identified coverage gaps in platform-specific code requiring mocking
  - Target: 75-78% realistic without major refactoring
  - Detailed analysis in `docs/pdca/coverage/plan.md` and `docs/pdca/phpstan9/plan.md`

### Testing
- Enhanced `CpuDetectorTest` with additional edge case coverage:
  - Environment variable detection scenarios
  - Concurrent detection request handling
  - Cache management across multiple instances
  - Boundary validation for CPU core counts
  - Cross-OS family detection verification

## [1.1.1] - 2025-11-11

### Added
- **Laravel Telescope Integration**: Complete integration with Laravel Telescope for debugging and profiling parallel tasks
- Task lifecycle tracking with automatic recording in Telescope dashboard
- Smart data truncation (1KB results, 100 array items, 10 stack trace frames) to prevent database bloat
- Memory leak protection with TTL-based cleanup (5min TTL, 1000 max active tasks) for Octane/Swoole compatibility
- Six automatic tag types for filtering: `parallel`, `parallel:completed`, `parallel:failed`, `task:{key}`, `exception:{class}`, `duration:{range}`
- Configuration options for Telescope integration:
  - `PARALLEL_TELESCOPE_ENABLED` (default: true) - Toggle Telescope recording
  - `PARALLEL_TELESCOPE_RECORD_TASKS` (default: true) - Control task detail recording
- `ParallelTelescopeServiceProvider` for automatic registration when Telescope is installed
- `ParallelTaskWatcher` extending Laravel Telescope's watcher system
- Event-driven recording listening to TaskStarted, TaskCompleted, and TaskFailed events
- Graceful degradation when Telescope is not installed

### Documentation
- Added comprehensive Telescope integration guide at `docs/telescope-integration.md`
- Updated "Integrations" section in main README.md with Telescope usage examples
- Included configuration options, dashboard filtering, and debugging workflows
- Documented minimal performance overhead (~0.5-1ms per task)
- Added troubleshooting guide for common Telescope integration scenarios

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

[Unreleased]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/laravel-parallel/laravel-parallel/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/laravel-parallel/laravel-parallel/releases/tag/v1.0.0
