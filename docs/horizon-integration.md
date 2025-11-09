# Laravel Horizon Integration

Laravel Parallel integrates seamlessly with [Laravel Horizon](https://laravel.com/docs/horizon) to provide real-time monitoring and metrics for your parallel job execution. This integration allows you to track parallel task performance, throughput, failures, and resource utilization through Horizon's dashboard.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [Usage](#usage)
- [API Endpoints](#api-endpoints)
- [Dashboard Integration](#dashboard-integration)
- [Troubleshooting](#troubleshooting)
- [Performance Considerations](#performance-considerations)

---

## Prerequisites

Before integrating Laravel Parallel with Horizon, ensure you have:

1. **Laravel 11.x** installed
2. **Laravel Horizon 5.x** installed and configured
3. **Redis** server running (required by Horizon)
4. **Laravel Parallel** package installed

### Installing Laravel Horizon

If you haven't installed Horizon yet:

```bash
composer require laravel/horizon

php artisan horizon:install

php artisan migrate
```

Configure Horizon in `config/horizon.php` and start the Horizon worker:

```bash
php artisan horizon
```

For detailed Horizon setup, see the [official documentation](https://laravel.com/docs/horizon).

---

## Installation

The Horizon integration is included with Laravel Parallel and requires no additional packages.

### Step 1: Verify Horizon Detection

Laravel Parallel automatically detects if Horizon is installed. To verify:

```php
use LaravelParallel\Integrations\Horizon\ParallelHorizonServiceProvider;

$provider = new ParallelHorizonServiceProvider(app());
$isInstalled = $provider->horizonIsInstalled(); // true if Horizon is available
```

### Step 2: Publish Configuration (Optional)

If you want to customize the integration settings:

```bash
php artisan vendor:publish --tag=parallel-config
```

This publishes the configuration to `config/parallel.php`.

---

## Configuration

The Horizon integration is configured through the `config/parallel.php` file:

```php
<?php

return [
    // ... other parallel configuration

    'horizon' => [
        /*
         * Enable or disable Horizon integration
         */
        'enabled' => env('PARALLEL_HORIZON_ENABLED', true),

        /*
         * Redis connection to use for Horizon metrics
         * Must match a connection in config/database.php
         */
        'redis_connection' => env('PARALLEL_HORIZON_REDIS_CONNECTION', 'default'),
    ],
];
```

### Environment Variables

Add these to your `.env` file to customize the integration:

```env
# Enable/disable Horizon integration (default: true)
PARALLEL_HORIZON_ENABLED=true

# Redis connection for metrics (default: "default")
PARALLEL_HORIZON_REDIS_CONNECTION=default
```

### Redis Connection

The integration uses Redis to store metrics. Ensure your Redis connection is properly configured in `config/database.php`:

```php
'redis' => [
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
],
```

---

## Features

The Horizon integration provides:

### 1. Automatic Metrics Recording

All parallel tasks are automatically tracked with:
- Start/completion timestamps
- Execution times
- Success/failure status
- Worker pool utilization
- Throughput (jobs per minute)

### 2. Horizon Dashboard Tags

Parallel jobs appear in Horizon with automatic tagging:
- `parallel` - All parallel jobs
- `parallel:{TaskName}` - Specific task type
- `pool:{PoolName}` - Worker pool identifier

Example: A task named "ProcessImages" running in the "batch" pool will have tags:
```
parallel, parallel:ProcessImages, pool:batch
```

### 3. Real-Time Statistics API

Four REST API endpoints provide metrics:
- **Overall Statistics** - Jobs/min, failures, active workers
- **Workload Breakdown** - Per-task success rates and wait times
- **Recent Jobs** - Paginated list of recently executed tasks
- **Active Workers** - Currently running worker processes

### 4. Event-Driven Architecture

The integration listens to Laravel Parallel events:
- `TaskStarted` - Records job initiation
- `TaskCompleted` - Records successful completion with execution time
- `TaskFailed` - Records failures with exception details

No manual instrumentation required!

---

## Usage

### Basic Usage

Simply use Laravel Parallel as normal - metrics are recorded automatically:

```php
use LaravelParallel\Facades\Parallel;

$results = Parallel::run([
    'task1' => fn() => processData(1),
    'task2' => fn() => processData(2),
    'task3' => fn() => processData(3),
]);

// Metrics are automatically recorded to Horizon!
```

### Viewing Metrics in Horizon

1. Start Horizon:
   ```bash
   php artisan horizon
   ```

2. Open the Horizon dashboard:
   ```
   http://your-app.test/horizon
   ```

3. Navigate to the **Jobs** tab and filter by tag `parallel` to see all parallel tasks.

### Accessing Metrics Programmatically

Use the `HorizonMetricsBridge` to retrieve metrics:

```php
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;

$bridge = app(HorizonMetricsBridge::class);

// Get overall statistics
$stats = $bridge->getStats();
// Returns: ['failedJobs' => 5, 'jobsPerMinute' => 120.5, 'processes' => 4, ...]

// Get workload breakdown
$workload = $bridge->getWorkload();
// Returns: ['tasks' => ['task1' => ['started' => 100, 'failed' => 2, ...]], ...]

// Get recent jobs
$recentJobs = $bridge->getRecentJobs(limit: 50);
// Returns: [['task_key' => 'task1', 'timestamp' => 1699564800.123, ...], ...]

// Get active workers
$workers = $bridge->getActiveWorkers();
// Returns: [['id' => 'worker-1', 'started_at' => 1699564800, ...], ...]
```

### Monitoring Specific Tasks

Tag your tasks for easier filtering in Horizon:

```php
$results = Parallel::run([
    'image-processing' => fn() => processImage(),
    'data-import' => fn() => importData(),
]);

// These will appear with tags:
// - parallel:image-processing
// - parallel:data-import
```

---

## API Endpoints

The integration exposes four HTTP endpoints for consuming metrics:

### Base URL

All endpoints are prefixed with `/horizon/api/parallel`:

```
http://your-app.test/horizon/api/parallel/stats
```

### Authentication

By default, endpoints inherit Horizon's middleware configuration. If you have authentication enabled in `config/horizon.php`, it will apply to these endpoints:

```php
// config/horizon.php
'middleware' => ['web', 'auth'], // Requires authentication
```

### Available Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/stats` | GET | Overall statistics (jobs/min, failures, active workers) |
| `/workload` | GET | Task workload breakdown with success rates |
| `/jobs/recent` | GET | Recent jobs list with pagination (`?limit=100`) |
| `/workers` | GET | Active worker processes information |

### Example API Responses

#### GET /horizon/api/parallel/stats

```json
{
  "failedJobs": 5,
  "jobsPerMinute": 120.5,
  "processes": 4,
  "status": "running"
}
```

#### GET /horizon/api/parallel/workload

```json
{
  "tasks": {
    "task1": {
      "started": 100,
      "failed": 2,
      "success_rate": 98.0,
      "avg_execution_time": 1.25
    }
  }
}
```

#### GET /horizon/api/parallel/jobs/recent?limit=10

```json
[
  {
    "task_key": "task1",
    "timestamp": 1699564800.123,
    "status": "completed",
    "execution_time": 1.25
  }
]
```

#### GET /horizon/api/parallel/workers

```json
[
  {
    "id": "worker-1",
    "started_at": 1699564800,
    "status": "active"
  }
]
```

---

## Dashboard Integration

### Option 1: Horizon Tags (Current)

Use Horizon's built-in filtering to view parallel jobs:

1. Open Horizon dashboard
2. Click on **Jobs** tab
3. Filter by tag: `parallel`
4. View detailed metrics for each job

### Option 2: Custom Dashboard (Future)

A dedicated Vue.js dashboard component is planned for future release (v2.1.0), which will provide:
- Real-time metrics cards
- Execution time charts
- Recent jobs table with live updates
- Worker pool utilization graphs

---

## Troubleshooting

### Integration Not Working

**Symptom:** Metrics are not appearing in Horizon

**Possible Causes:**

1. **Horizon not installed:**
   ```bash
   composer show laravel/horizon
   # Should display version 5.x
   ```

2. **Integration disabled:**
   Check `config/parallel.php`:
   ```php
   'horizon' => ['enabled' => true],
   ```

3. **Horizon not running:**
   ```bash
   php artisan horizon
   # Should start without errors
   ```

4. **Redis connection issue:**
   Check `storage/logs/laravel.log` for Redis connection errors.

### No Jobs Appearing in Horizon

**Symptom:** Parallel tasks execute successfully but don't appear in Horizon

**Solutions:**

1. Verify Redis connection:
   ```bash
   php artisan tinker
   >>> Redis::connection()->ping()
   # Should return "+PONG"
   ```

2. Check Horizon middleware:
   ```php
   // config/horizon.php
   'middleware' => ['web'], // Ensure middleware doesn't block access
   ```

3. Verify event listeners are registered:
   ```bash
   php artisan event:list
   # Should show TaskStarted, TaskCompleted, TaskFailed events
   ```

### API Endpoints Return 500 Errors

**Symptom:** Calling `/horizon/api/parallel/stats` returns 500 Internal Server Error

**Solutions:**

1. Check Redis connection in `.env`:
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_DB=0
   ```

2. Ensure Horizon metrics bridge is bound:
   ```bash
   php artisan tinker
   >>> app()->bound(LaravelParallel\Integrations\Horizon\HorizonMetricsBridge::class)
   # Should return true
   ```

3. Check `storage/logs/laravel.log` for detailed error messages.

### High Memory Usage

**Symptom:** Redis memory usage grows continuously

**Cause:** Metrics data accumulates in Redis

**Solutions:**

The integration automatically limits stored data:
- Recent jobs: Limited to 100 entries
- Execution times: Limited to 100 per task

If memory is still an issue, consider:
1. Using a dedicated Redis instance for Horizon
2. Setting up Redis eviction policies in `redis.conf`:
   ```
   maxmemory 256mb
   maxmemory-policy allkeys-lru
   ```

### Events Not Firing

**Symptom:** Tasks execute but no events are recorded

**Solutions:**

1. Verify events are enabled in `config/parallel.php`:
   ```php
   'events' => ['enabled' => true],
   ```

2. Check event dispatcher is working:
   ```bash
   php artisan tinker
   >>> Event::dispatch(new \LaravelParallel\Events\TaskStarted('test', 1, 4))
   ```

3. Clear application cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

---

## Performance Considerations

### Minimal Overhead

The Horizon integration is designed to have negligible performance impact:
- **<0.5ms** per task operation
- Metrics recorded asynchronously via event listeners
- No blocking I/O during task execution

### Production Recommendations

1. **Use a dedicated Redis instance** for Horizon metrics to avoid contention with cache or session data
2. **Monitor Redis memory** usage with `INFO memory` command
3. **Disable verbose logging** in production:
   ```env
   PARALLEL_LOG_TASK_DETAILS=false
   ```

4. **Set appropriate worker counts** based on your server's CPU cores:
   ```php
   Parallel::workers(8)->run($tasks); // For 8-core server
   ```

5. **Configure Redis persistence** appropriately:
   - Development: No persistence needed
   - Production: Consider RDB snapshots for metrics retention

### Scaling Considerations

For high-throughput applications (>1000 tasks/minute):
- Use Redis Cluster for distributed metrics storage
- Consider implementing metric aggregation intervals
- Monitor Horizon queue worker memory consumption
- Implement automated cleanup of old metrics data

---

## Next Steps

- **[Main Documentation](../README.md)** - Full Laravel Parallel documentation
- **[Laravel Horizon Documentation](https://laravel.com/docs/horizon)** - Official Horizon guide
- **[Configuration Reference](../config/parallel.php)** - Complete configuration options

---

## Support

If you encounter issues not covered in this guide:

1. Review the troubleshooting section above
2. Check [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
3. Open an issue on [GitHub](https://github.com/giorgospatelis/laravel-parallel/issues)
4. Email support: giorgospatelis@outlook.com
