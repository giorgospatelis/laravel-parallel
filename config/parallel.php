<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Workers
    |--------------------------------------------------------------------------
    |
    | The default number of worker processes to use. If null, the number
    | of CPU cores will be automatically detected.
    |
    */
    'default_workers' => env('PARALLEL_WORKERS', null),

    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | The default timeout for parallel tasks in seconds.
    |
    */
    'default_timeout' => env('PARALLEL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Maximum Workers
    |--------------------------------------------------------------------------
    |
    | The maximum number of worker processes allowed.
    |
    */
    'max_workers' => env('PARALLEL_MAX_WORKERS', 128),

    /*
    |--------------------------------------------------------------------------
    | Resource Limits
    |--------------------------------------------------------------------------
    |
    | These limits protect against resource exhaustion and DoS attacks.
    |
    */

    /*
    | Maximum Tasks Per Batch
    |
    | Maximum number of tasks that can be submitted in a single batch.
    | This prevents memory exhaustion from processing millions of tasks.
    | Set to 0 to disable the limit (not recommended for production).
    */
    'max_tasks_per_batch' => env('PARALLEL_MAX_TASKS', 10000),

    /*
    | Auto Chunk Large Batches
    |
    | When enabled, batches exceeding max_tasks_per_batch will be
    | automatically chunked and processed in multiple batches.
    | When disabled, exceeding the limit throws an exception.
    */
    'auto_chunk' => env('PARALLEL_AUTO_CHUNK', true),

    /*
    | Chunk Size
    |
    | Size of chunks when auto-chunking large batches.
    | Should be less than or equal to max_tasks_per_batch.
    */
    'chunk_size' => env('PARALLEL_CHUNK_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for parallel task execution. Logs provide visibility
    | into task performance, errors, and system behavior.
    |
    */
    'logging' => [
        /*
        | Enable or disable logging for parallel operations.
        | Logs help with debugging and monitoring in production.
        */
        'enabled' => env('PARALLEL_LOGGING_ENABLED', true),

        /*
        | The log channel to use. Must be configured in config/logging.php
        | Defaults to 'stack' which uses your application's default logging.
        */
        'channel' => env('PARALLEL_LOG_CHANNEL', 'stack'),

        /*
        | Minimum log level for parallel operations.
        | Options: debug, info, notice, warning, error, critical, alert, emergency
        */
        'level' => env('PARALLEL_LOG_LEVEL', 'info'),

        /*
        | Log detailed task information (may impact performance for large batches)
        | When false, only batch-level statistics are logged.
        */
        'log_task_details' => env('PARALLEL_LOG_TASK_DETAILS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Configuration for task lifecycle events.
    |
    */
    'events' => [
        /*
        | Enable or disable event dispatching for task lifecycle.
        | When enabled, TaskStarted, TaskCompleted, and TaskFailed events
        | will be dispatched during parallel execution.
        */
        'enabled' => env('PARALLEL_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Horizon Integration
    |--------------------------------------------------------------------------
    |
    | Configure integration with Laravel Horizon for monitoring parallel tasks.
    | When enabled, parallel task metrics will be recorded to Redis using
    | Horizon's schema, allowing tasks to be monitored via Horizon dashboard.
    |
    */
    'horizon' => [
        /*
        | Enable or disable Horizon integration.
        | When enabled, parallel task metrics will be recorded to Redis.
        | Requires Laravel Horizon to be installed.
        */
        'enabled' => env('PARALLEL_HORIZON_ENABLED', true),

        /*
        | The Redis connection to use for Horizon metrics.
        | Should match one of your configured Redis connections.
        */
        'redis_connection' => env('PARALLEL_HORIZON_REDIS_CONNECTION', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Telescope Integration
    |--------------------------------------------------------------------------
    |
    | Configure integration with Laravel Telescope for debugging and profiling
    | parallel tasks. When enabled, task execution details will be recorded
    | in Telescope for inspection via the Telescope dashboard.
    |
    | STORAGE IMPLICATIONS:
    | - Each task creates 1-3 entries in telescope_entries table (started, completed/failed)
    | - Average storage per task: ~2-5KB depending on result size and stack traces
    | - 1000 tasks = ~2-5MB of database storage
    | - Use Telescope's data pruning to manage storage (telescope:prune command)
    |
    | PERFORMANCE IMPACT:
    | - Minimal overhead when record_tasks is enabled (~0.5-1ms per task)
    | - Event listener overhead even when disabled (~0.1ms per task)
    | - Database writes are asynchronous and don't block task execution
    | - Recommended: Disable in production for high-volume workloads (>1000 tasks/min)
    |
    */
    'telescope' => [
        /*
        | Enable or disable Telescope integration.
        | When enabled, parallel task execution will be recorded in Telescope.
        | Requires Laravel Telescope to be installed.
        |
        | Set to false to completely disable integration (no event listeners registered).
        */
        'enabled' => env('PARALLEL_TELESCOPE_ENABLED', true),

        /*
        | Record individual task details.
        | When enabled, detailed information about each task will be captured:
        | - Task start time and key
        | - Execution time and result (truncated if large)
        | - Exception details with stack traces (for failures)
        | - Tags for filtering (parallel, parallel:started/completed/failed)
        |
        | WHEN TO DISABLE:
        | - Production environments with >1000 tasks/minute
        | - When task results are very large (>10KB per task)
        | - When you only need batch-level statistics (use logging instead)
        |
        | WHEN TO ENABLE:
        | - Development and staging environments
        | - Debugging task failures and performance issues
        | - Profiling parallel execution patterns
        | - Low to medium volume workloads (<1000 tasks/minute)
        */
        'record_tasks' => env('PARALLEL_TELESCOPE_RECORD_TASKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Benchmarking
    |--------------------------------------------------------------------------
    |
    | Configuration for the performance benchmarking suite. Benchmarks help
    | measure parallel processing performance across different workload types
    | and identify optimal worker configurations.
    |
    */
    'benchmarking' => [
        /*
        | Enable or disable the benchmarking suite.
        | When disabled, the parallel:benchmark command will not be available.
        */
        'enabled' => env('PARALLEL_BENCHMARKING_ENABLED', true),

        /*
        | Default number of iterations per benchmark scenario.
        | Higher values provide more accurate results but take longer.
        */
        'default_iterations' => env('PARALLEL_BENCHMARK_ITERATIONS', 100),

        /*
        | Default number of workers for benchmarks.
        | null = auto-detect CPU cores
        */
        'default_workers' => env('PARALLEL_BENCHMARK_WORKERS', null),

        /*
        | Available benchmark scenarios.
        | You can register custom scenarios by adding them here.
        */
        'scenarios' => [
            'cpu-bound' => LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario::class,
            'io-bound' => LaravelParallel\Benchmarking\Scenarios\IoBoundScenario::class,
            'mixed' => LaravelParallel\Benchmarking\Scenarios\MixedWorkloadScenario::class,
            'memory' => LaravelParallel\Benchmarking\Scenarios\MemoryIntensiveScenario::class,
        ],
    ],
];
