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
];
