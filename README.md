<p align="center">
    <img src="art/banner.jpg" alt="Laravel Parallel Banner" width="100%">
</p>

<h1 align="center">Laravel Parallel</h1>

<p align="center">
    <strong>Simple parallel processing for Laravel - Powered by AMPHP with automatic CPU detection.</strong>
</p>

<p align="center">
    <a href="https://packagist.org/packages/laravel-parallel/laravel-parallel"><img src="https://img.shields.io/packagist/v/laravel-parallel/laravel-parallel" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/laravel-parallel/laravel-parallel"><img src="https://img.shields.io/packagist/dt/laravel-parallel/laravel-parallel" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/laravel-parallel/laravel-parallel"><img src="https://img.shields.io/packagist/l/laravel-parallel/laravel-parallel" alt="License"></a>
    <a href="https://packagist.org/packages/laravel-parallel/laravel-parallel"><img src="https://img.shields.io/packagist/php-v/laravel-parallel/laravel-parallel" alt="PHP Version"></a>
</p>

---

Laravel Parallel provides an intuitive, Laravel-style API for true parallel processing in your applications. Built on the battle-tested [amphp/parallel](https://github.com/amphp/parallel) library, it enables you to execute CPU-intensive tasks concurrently with automatic CPU core detection and comprehensive error handling.

## Features

- **Intuitive API** - Fluent, Laravel-style interface with method chaining
- **Automatic Optimization** - Detects CPU cores for optimal parallelization
- **True Parallelism** - Real multi-process execution, not just async/concurrent
- **Rich Result Handling** - Comprehensive result collection with metrics and filtering
- **Laravel Integration** - Works seamlessly with Octane, Horizon, and other Laravel features
- **Type-Safe** - PHPStan Level 8 compliant with full type coverage
- **Extensible Architecture** - Built on SOLID principles with clear extension points
- **Production Ready** - Comprehensive error handling, logging, and monitoring
- **Event-Driven** - Task lifecycle events for observability and monitoring

## Installation

You can install the package via Composer:

```bash
composer require laravel-parallel/laravel-parallel
```

### Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher

| Laravel Version | Package Version |
|----------------|-----------------|
| 11.x, 12.x     | ^2.0            |

### Configuration (Optional)

Publish the configuration file:

```bash
php artisan vendor:publish --tag=parallel-config
```

This will create a `config/parallel.php` file where you can customize default settings.

## Quick Start

Execute tasks in parallel with a simple, fluent API:

```php
use LaravelParallel\Facades\Parallel;

$results = Parallel::run([
    'user_report' => fn() => generateUserReport(),
    'sales_data' => fn() => fetchSalesData(),
    'inventory' => fn() => syncInventory(),
]);

foreach ($results as $key => $result) {
    if ($result->isSuccess()) {
        echo "Task {$key}: " . $result->getValue();
    }
}
```

That's it! Laravel Parallel automatically detects your CPU cores and distributes work efficiently.

## Usage Examples

### Basic Parallel Execution

Execute multiple tasks concurrently:

```php
use LaravelParallel\Facades\Parallel;

$results = Parallel::run([
    'task1' => fn() => expensive_operation_1(),
    'task2' => fn() => expensive_operation_2(),
    'task3' => fn() => expensive_operation_3(),
]);

foreach ($results as $key => $result) {
    if ($result->isSuccess()) {
        echo "Task {$key}: {$result->getValue()}\n";
    } else {
        echo "Task {$key} failed: {$result->getException()->getMessage()}\n";
    }
}
```

### Parallel Map Operation

Process collections in parallel:

```php
$users = User::all();

$results = Parallel::map($users, function ($user) {
    return [
        'id' => $user->id,
        'score' => calculateComplexScore($user),
    ];
});

// Extract all values
$scores = collect($results)->map->getValue()->all();
```

### Configuration

Customize worker count and timeout:

```php
$results = Parallel::workers(4)
    ->timeout(30.0)
    ->run([
        'heavy1' => fn() => processLargeDataset(),
        'heavy2' => fn() => runComplexCalculation(),
    ]);
```

### Real-World Example: Parallel API Requests

Fetch multiple API endpoints simultaneously:

```php
$endpoints = [
    'users' => 'https://api.example.com/users',
    'posts' => 'https://api.example.com/posts',
    'comments' => 'https://api.example.com/comments',
];

$results = Parallel::map($endpoints, function ($url) {
    return Http::get($url)->json();
});

$data = collect($results)
    ->filter->isSuccess()
    ->map->getValue()
    ->all();
```

## Configuration

The package works out-of-the-box with sensible defaults. You can customize behavior via the config file or environment variables:

| Config Key | Env Variable | Default | Description |
|-----------|-------------|---------|-------------|
| `default_workers` | `PARALLEL_WORKERS` | `null` (auto-detect) | Number of worker processes |
| `default_timeout` | `PARALLEL_TIMEOUT` | `30` | Timeout in seconds |
| `max_workers` | `PARALLEL_MAX_WORKERS` | `128` | Maximum workers allowed |
| `max_tasks_per_batch` | `PARALLEL_MAX_TASKS` | `10000` | Max tasks per batch |
| `auto_chunk` | `PARALLEL_AUTO_CHUNK` | `true` | Auto-chunk large batches |
| `chunk_size` | `PARALLEL_CHUNK_SIZE` | `1000` | Size of chunks |
| `events.enabled` | `PARALLEL_EVENTS_ENABLED` | `true` | Enable task events |
| `logging.enabled` | `PARALLEL_LOGGING_ENABLED` | `true` | Enable logging |
| `logging.channel` | `PARALLEL_LOG_CHANNEL` | `stack` | Log channel |
| `logging.level` | `PARALLEL_LOG_LEVEL` | `info` | Minimum log level |

See the [full configuration file](config/parallel.php) for all options and detailed descriptions.

## Advanced Usage

### Working with Results

The result collection provides powerful methods for handling parallel execution outcomes:

```php
use LaravelParallel\Results\ResultCollection;

$results = new ResultCollection(Parallel::run($tasks));

// Filter successful results
$successful = $results->successful();

// Filter failed results
$failed = $results->failed();

// Extract values with default for failures
$values = $results->valuesOr('default_value');

// Get all exceptions
$exceptions = $results->exceptions();

// Check if all succeeded
if ($results->allSuccessful()) {
    echo "All tasks completed successfully!\n";
}
```

### Execution Metrics

Track performance with built-in metrics:

```php
use LaravelParallel\Results\ExecutionMetrics;

$results = new ResultCollection(Parallel::run($tasks));
$metrics = ExecutionMetrics::fromResults($results);

echo "Total tasks: {$metrics->totalTasks}\n";
echo "Successful: {$metrics->successfulTasks}\n";
echo "Failed: {$metrics->failedTasks}\n";
echo "Success rate: {$metrics->successRate()}%\n";
echo "Total time: {$metrics->totalExecutionTime}s\n";
echo "Average time: {$metrics->averageExecutionTime}s\n";
```

### Error Handling

Handle failures gracefully:

```php
$results = Parallel::run($tasks);

foreach ($results as $key => $result) {
    $result
        ->ifSuccess(fn($value) => processValue($value))
        ->ifFailure(fn($exception) => handleError($exception));
}
```

### Event Listeners

Listen to task lifecycle events:

```php
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;

Event::listen(TaskCompleted::class, function ($event) {
    Log::info("Task {$event->taskKey} completed in {$event->executionTime}s");
});

Event::listen(TaskFailed::class, function ($event) {
    Log::error("Task {$event->taskKey} failed", [
        'error' => $event->exception->getMessage(),
    ]);
});
```

## Integrations

Laravel Parallel integrates seamlessly with popular Laravel ecosystem tools to enhance monitoring, observability, and developer experience.

### Laravel Horizon Integration

Monitor your parallel tasks in real-time through Laravel Horizon's dashboard. The Horizon integration provides:

- **Automatic metrics recording** for all parallel tasks
- **Real-time statistics** via REST API endpoints
- **Dashboard tagging** for easy filtering and monitoring
- **Event-driven architecture** with zero configuration required

The integration is included with the package and activates automatically when Laravel Horizon is detected. Simply install Horizon and start using Laravel Parallel - metrics will appear in your Horizon dashboard immediately.

**Quick Start:**

```php
// Execute parallel tasks as normal
$results = Parallel::run([
    'task1' => fn() => processData(1),
    'task2' => fn() => processData(2),
]);

// View metrics in Horizon dashboard at /horizon
// Filter by tag "parallel" to see all parallel tasks
```

For detailed setup instructions, API documentation, and advanced configuration options, see the [Horizon Integration Guide](docs/horizon-integration.md).

### Future Integrations

Planned integrations for upcoming releases:

- **Laravel Telescope** - Debug parallel execution with detailed query and event tracking
- **Laravel Pulse** - Real-time performance monitoring and alerting
- **Sentry** - Advanced error tracking and performance monitoring

## Extending the Package

Laravel Parallel is built with extensibility in mind. You can create:

- **Custom Tasks**: Implement `TaskContract` for specialized task types
- **Custom Executors**: Implement `ExecutorContract` for alternative execution strategies
- **Custom Result Handlers**: Extend `ResultCollection` for domain-specific operations
- **Middleware**: Intercept task execution for logging, monitoring, etc.

### Creating a Custom Task

```php
use LaravelParallel\Tasks\AbstractTask;
use Amp\Cancellation;
use Amp\Sync\Channel;

final class DatabaseQueryTask extends AbstractTask
{
    public function __construct(
        private readonly string $query,
        private readonly array $bindings = [],
    ) {
        parent::__construct();
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        // This runs in a worker process
        $pdo = new PDO(/* connection details */);
        $stmt = $pdo->prepare($this->query);
        $stmt->execute($this->bindings);

        return $stmt->fetchAll();
    }
}
```

### Best Practices

1. **Keep Tasks Self-Contained**: Tasks should contain all data needed for execution
2. **Handle Serialization**: Ensure all task data is serializable
3. **Resource Management**: Recreate database connections and resources in worker processes
4. **Error Handling**: Always handle exceptions and return meaningful error information

## Architecture

Laravel Parallel follows **Hexagonal Architecture** (Ports & Adapters) and **SOLID** principles for maximum maintainability and extensibility.

```
┌─────────────────────────────────────────┐
│         Laravel Application             │
└──────────────┬──────────────────────────┘
               │
        ┌──────▼──────┐
        │  Parallel   │  (Facade)
        │  Facade     │
        └──────┬──────┘
               │
        ┌──────▼──────────┐
        │ ParallelManager │  (Core)
        └──────┬──────────┘
               │
        ┌──────▼──────────┐
        │    Executor     │  (Core)
        └──────┬──────────┘
               │
     ┌─────────┴─────────┐
     │                   │
┌────▼────────┐     ┌────▼────────┐
│  Worker     │     │  Worker     │
│  Pool       │ ... │  Pool       │
│  Manager    │     │  Manager    │
└─────────────┘     └─────────────┘
```

### Key Components

- **Core**: Domain logic (ParallelManager, Executor, ResultCollector)
- **Contracts**: Interfaces for extensibility (TaskContract, ExecutorContract, etc.)
- **Tasks**: Executable work units with serialization support
- **Workers**: Worker pool management and lifecycle
- **Results**: Result handling with rich metrics

The package wraps the powerful [amphp/parallel](https://github.com/amphp/parallel) library with a Laravel-friendly interface.

### Design Patterns

- **Facade Pattern**: Static access via `Parallel` facade
- **Factory Pattern**: Worker pool creation with dependency injection
- **Value Object Pattern**: Immutable configuration objects
- **Strategy Pattern**: Different task types via `TaskContract`
- **Dependency Injection**: Constructor injection throughout

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

Run static analysis:

```bash
composer phpstan
```

The package maintains:
- PHPStan Level 8 compliance
- 77.5% test coverage (251 tests, 672 assertions)
- Comprehensive unit and feature tests

## Performance

### When to Use Laravel Parallel

**Ideal For:**
- CPU-bound tasks (data processing, transformations, calculations)
- Task sets with 10 or more tasks
- Tasks taking 100ms or longer each
- Immediate results needed (not background processing)
- Laravel applications prioritizing developer experience

**Better Alternatives:**
- **Micro-tasks (<100ms)**: Use sequential processing
- **I/O-bound operations**: Use Laravel Queues or async HTTP
- **Background jobs**: Use Laravel Queues with horizon
- **Large data payloads (>1MB)**: Consider streaming or chunking

### Overhead Analysis

- **Setup overhead**: ~2-5ms per execution
- **Per-task overhead**: ~0.5-1ms (serialization + IPC)
- **Break-even point**: 10 tasks × 100ms = 1 second total work

### Example Performance

```php
// Small Workload (Not Recommended)
// 5 tasks × 100ms = 500ms sequential
// Parallel: ~150ms (3.2x speedup, 3% overhead)

// Medium Workload (Recommended)
// 50 tasks × 1s = 50s sequential
// Parallel (5 cores): ~10s (4.98x speedup, 0.3% overhead)

// Large Workload (Excellent)
// 1000 tasks × 500ms = 500s sequential
// Parallel (8 cores): ~63s (7.94x speedup, 0.8% overhead)
```

### Performance Tips

**1. Optimal Worker Count**
```php
// For CPU-bound tasks, use CPU count
$results = Parallel::workers(CPU_COUNT)->run($cpuTasks);

// For I/O-bound tasks, use 2-3x CPU count
$results = Parallel::workers(CPU_COUNT * 3)->run($ioTasks);
```

**2. Chunk Large Datasets**
```php
$items = range(1, 100000);
$chunks = array_chunk($items, 1000);

$results = Parallel::map($chunks, function ($chunk) {
    return array_map(fn($n) => process($n), $chunk);
});
```

**3. Balance Task Size**
```php
// Bad: Too many small tasks (high overhead)
$results = Parallel::map(range(1, 100000), fn($n) => $n * 2);

// Good: Reasonable task granularity
$chunks = array_chunk(range(1, 100000), 1000);
$results = Parallel::map($chunks, fn($chunk) => array_map(fn($n) => $n * 2, $chunk));
```

## Frequently Asked Questions

### Can I use this with Laravel Octane/Swoole?

**Yes!** Laravel Parallel v2.0+ is fully compatible with Laravel Octane and Swoole. The package uses non-singleton bindings to prevent state leakage between requests.

### How is this different from Laravel Queues?

Laravel Queues are designed for background processing with delayed execution, while Laravel Parallel is for immediate parallel execution:

- **Queues**: Background processing, persistent storage, delayed execution, job retries
- **Parallel**: Immediate execution, in-memory processing, real-time results, CPU-intensive tasks

Use queues for background jobs, use parallel processing for immediate CPU-intensive operations.

### How many workers should I use?

The optimal number depends on your task type:

- **CPU-bound tasks**: Number of CPU cores (auto-detected by default)
- **I/O-bound tasks**: 2-3x CPU cores
- **Mixed workloads**: Start with CPU count and adjust based on monitoring

### What happens if a task throws an exception?

Exceptions are caught and wrapped in a `ParallelResult` with `isFailure() === true`. Other tasks continue executing independently. You can handle failures gracefully:

```php
foreach ($results as $result) {
    if ($result->isFailure()) {
        $exception = $result->getException();
        // Handle the error
    }
}
```

### Can I use database connections in parallel tasks?

Yes, but you need to be aware that each worker process has its own memory space. Database connections should be recreated within each task:

```php
$results = Parallel::map($items, function ($item) {
    // Each worker recreates the connection
    $user = User::find($item['user_id']);
    return processUser($user);
});
```

### How do I retry failed tasks?

Extract failed task keys and rerun them:

```php
$results = new ResultCollection(Parallel::run($tasks));
$failedTasks = $results->failed()->keys()->all();

if (count($failedTasks) > 0) {
    $retryTasks = collect($tasks)->only($failedTasks)->all();
    $retryResults = Parallel::run($retryTasks);
}
```

### Is this package production-ready?

Yes! Laravel Parallel v2.0+ is built with production in mind:

- PHPStan Level 8 compliance (maximum type safety)
- Comprehensive test coverage (77.5% with 251 tests and 672 assertions)
- Battle-tested amphp/parallel foundation
- Used in production Laravel applications
- Octane/Swoole compatible

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes and version history.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability, please email giorgospatelis@outlook.com. All security vulnerabilities will be promptly addressed.

## Credits

- [Giorgos Patelis](https://github.com/giorgospatelis)
- Powered by [amphp/parallel](https://github.com/amphp/parallel)
- All [contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
