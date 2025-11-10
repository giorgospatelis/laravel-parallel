# Laravel Telescope Integration

Laravel Parallel integrates seamlessly with [Laravel Telescope](https://laravel.com/docs/telescope) to provide powerful debugging and profiling capabilities for your parallel task execution. This integration allows you to track task lifecycle, execution metrics, failures, and performance characteristics through Telescope's intuitive dashboard.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [Usage](#usage)
- [Dashboard Integration](#dashboard-integration)
- [Troubleshooting](#troubleshooting)
- [Performance Considerations](#performance-considerations)
- [Next Steps](#next-steps)
- [Support](#support)

---

## Prerequisites

Before integrating Laravel Parallel with Telescope, ensure you have:

1. **Laravel 11.x** installed
2. **Laravel Telescope 5.x** installed and configured
3. **Laravel Parallel** package installed
4. **Database** configured for Telescope storage

### Installing Laravel Telescope

If you haven't installed Telescope yet:

```bash
composer require laravel/telescope

php artisan telescope:install

php artisan migrate
```

Configure Telescope in `config/telescope.php` and verify it's accessible:

```
http://your-app.test/telescope
```

For detailed Telescope setup, see the [official documentation](https://laravel.com/docs/telescope).

---

## Installation

The Telescope integration is included with Laravel Parallel and requires no additional packages.

### Step 1: Verify Telescope Detection

Laravel Parallel automatically detects if Telescope is installed. To verify:

```php
use LaravelParallel\Integrations\Telescope\ParallelTelescopeServiceProvider;

$provider = new ParallelTelescopeServiceProvider(app());
// Telescope integration automatically registers when available
```

### Step 2: Publish Configuration (Optional)

If you want to customize the integration settings:

```bash
php artisan vendor:publish --tag=parallel-config
```

This publishes the configuration to `config/parallel.php`.

---

## Configuration

The Telescope integration is configured through the `config/parallel.php` file:

```php
<?php

return [
    // ... other parallel configuration

    'telescope' => [
        /*
         * Enable or disable Telescope integration
         */
        'enabled' => env('PARALLEL_TELESCOPE_ENABLED', true),

        /*
         * Record individual task details in Telescope
         * Disable in high-volume production environments to reduce overhead
         */
        'record_tasks' => env('PARALLEL_TELESCOPE_RECORD_TASKS', true),
    ],
];
```

### Environment Variables

Add these to your `.env` file to customize the integration:

```env
# Enable/disable Telescope integration (default: true)
PARALLEL_TELESCOPE_ENABLED=true

# Record detailed task information (default: true)
# Set to false in production for high-volume workloads
PARALLEL_TELESCOPE_RECORD_TASKS=true
```

### Database Configuration

The integration uses Telescope's database storage. Ensure your database connection is properly configured in `config/database.php` and migrations have been run:

```bash
php artisan migrate
```

---

## Features

The Telescope integration provides:

### 1. Automatic Task Recording

All parallel tasks are automatically tracked with:
- Task start/completion timestamps
- Execution times in seconds
- Success/failure status
- Result values (with smart truncation)
- Exception details with stack traces
- Worker pool information

### 2. Task Lifecycle Tracking

Monitor the complete lifecycle of each task:
- **Started** - When task begins execution
- **Completed** - When task finishes successfully with result
- **Failed** - When task throws an exception with full error details

### 3. Smart Data Management

Prevents database bloat with intelligent data handling:
- **Result Truncation** - Strings limited to 1KB, arrays to 100 items
- **Stack Trace Limiting** - Exception traces limited to 10 frames
- **TTL-Based Cleanup** - 5-minute TTL with 1000 task maximum
- **Memory Leak Protection** - Compatible with Laravel Octane/Swoole

### 4. Powerful Filtering with Tags

Six automatic tag types for precise filtering:
- `parallel` - All parallel tasks
- `parallel:completed` - Successfully completed tasks
- `parallel:failed` - Failed tasks with exceptions
- `task:{key}` - Specific task by its key
- `exception:{class}` - Failed tasks by exception type (e.g., `exception:RuntimeException`)
- `duration:{range}` - Tasks by execution time (e.g., `duration:slow` for >1s tasks)

### 5. Event-Driven Architecture

The integration listens to Laravel Parallel events:
- `TaskStarted` - Records task initiation
- `TaskCompleted` - Records successful completion with execution time
- `TaskFailed` - Records failures with exception details

No manual instrumentation required!

---

## Usage

### Basic Usage

Simply use Laravel Parallel as normal - tasks are recorded automatically:

```php
use LaravelParallel\Facades\Parallel;

$results = Parallel::run([
    'user_report' => fn() => generateUserReport(),
    'sales_data' => fn() => fetchSalesData(),
    'inventory' => fn() => syncInventory(),
]);

// Tasks are automatically recorded to Telescope!
```

### Viewing Tasks in Telescope

1. Open the Telescope dashboard:
   ```
   http://your-app.test/telescope
   ```

2. Navigate to the **Parallel Task** section in the sidebar

3. View all parallel tasks with their execution details

### Filtering Tasks

Use Telescope's built-in tag filtering to find specific tasks:

```
# View all parallel tasks
Tag: parallel

# View only completed tasks
Tag: parallel:completed

# View only failed tasks
Tag: parallel:failed

# View specific task by key
Tag: task:user_report

# View tasks that threw RuntimeException
Tag: exception:RuntimeException

# View slow tasks (>1 second)
Tag: duration:slow
```

### Accessing Data Programmatically

Telescope entries are stored in the database and can be queried:

```php
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Storage\EntryQueryOptions;

// Get recent parallel tasks
$entries = app(\Laravel\Telescope\Contracts\EntriesRepository::class)
    ->get(
        EntryType::PARALLEL_TASK,
        new EntryQueryOptions(limit: 50)
    );

foreach ($entries as $entry) {
    echo "Task: {$entry->content['task_key']}\n";
    echo "Status: {$entry->content['status']}\n";
    echo "Execution Time: {$entry->content['execution_time']}s\n";
}
```

### Debugging Failed Tasks

View detailed exception information for failed tasks:

```php
// In Telescope dashboard, filter by parallel:failed tag
// Click on any failed task to see:

[
    'task_key' => 'inventory',
    'status' => 'failed',
    'execution_time' => 1.234,
    'exception' => [
        'class' => 'RuntimeException',
        'message' => 'Database connection failed',
        'code' => 0,
        'file' => '/app/Services/InventoryService.php',
        'line' => 42,
        'trace' => [...] // First 10 stack frames
    ],
    'started_at' => 1699564800.123,
    'failed_at' => 1699564801.357
]
```

---

## Dashboard Integration

### Viewing in Telescope UI

The integration provides a dedicated "Parallel Task" entry type in Telescope:

1. **Entry List View**
   - Shows all parallel tasks chronologically
   - Color-coded by status (green=completed, red=failed, blue=started)
   - Displays task key, execution time, and timestamp
   - Filter by tags using Telescope's tag selector

2. **Entry Detail View**
   - Click any task to see full details
   - View complete execution data:
     - Task key and status
     - Start/completion timestamps
     - Execution time in seconds
     - Result value (for completed tasks)
     - Exception details (for failed tasks)
   - See all associated tags
   - View related entries (queries, logs, etc.)

### Example Dashboard Workflow

**Scenario:** Debug why the "user_report" task is slow

1. Open Telescope dashboard
2. Click "Parallel Task" in sidebar
3. Filter by tag: `task:user_report`
4. Sort by execution time (descending)
5. Click slowest entry to view details
6. Check "Related Entries" to see:
   - Database queries during execution
   - Log entries
   - HTTP requests
   - Cache operations

### Integration with Other Telescope Features

Parallel task entries integrate with Telescope's ecosystem:

- **Queries Tab** - See database queries executed during task
- **Logs Tab** - View log entries from task execution
- **Exceptions Tab** - Failed tasks appear here too
- **Cache Tab** - Monitor cache operations in tasks
- **HTTP Tab** - See external API calls from tasks

---

## Troubleshooting

### Integration Not Working

**Symptom:** Tasks are not appearing in Telescope dashboard

**Possible Causes:**

1. **Telescope not installed:**
   ```bash
   composer show laravel/telescope
   # Should display version 5.x
   ```

2. **Integration disabled:**
   Check `config/parallel.php`:
   ```php
   'telescope' => [
       'enabled' => true,
       'record_tasks' => true,
   ],
   ```

3. **Events disabled:**
   Check `config/parallel.php`:
   ```php
   'events' => ['enabled' => true],
   ```

4. **Telescope not accessible:**
   Visit `/telescope` and ensure it loads correctly

### Tasks Not Being Recorded

**Symptom:** Parallel tasks execute but don't appear in Telescope

**Solutions:**

1. Verify Telescope is recording entries:
   ```bash
   php artisan tinker
   >>> config('telescope.enabled')
   # Should return true
   ```

2. Check database connection:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo()
   # Should connect without errors
   ```

3. Verify event listeners are registered:
   ```bash
   php artisan event:list
   # Should show TaskStarted, TaskCompleted, TaskFailed events
   ```

4. Clear application cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Missing Task Details

**Symptom:** Tasks show as "started" but never "completed" or "failed"

**Solutions:**

1. Check if tasks are actually completing:
   ```php
   $results = Parallel::run([
       'test' => fn() => 'success',
   ]);
   var_dump($results['test']->isSuccess()); // Should be true
   ```

2. Verify `record_tasks` is enabled:
   ```php
   config('parallel.telescope.record_tasks') // Should be true
   ```

3. Check for task timeout issues:
   ```php
   // Increase timeout if tasks take longer
   Parallel::timeout(60)->run($tasks);
   ```

4. Look in Telescope's Exceptions tab for errors during recording

### High Database Storage Usage

**Symptom:** Telescope database grows rapidly with parallel tasks

**Cause:** Large result sets or high task volume

**Solutions:**

The integration automatically limits stored data:
- Result strings: 1KB maximum
- Result arrays: 100 items maximum
- Stack traces: 10 frames maximum
- Active tasks: 1000 maximum with 5-minute TTL

If storage is still an issue:

1. **Disable detailed recording in production:**
   ```env
   PARALLEL_TELESCOPE_RECORD_TASKS=false
   ```

2. **Configure Telescope pruning:**
   ```bash
   # In config/telescope.php
   'prune' => [
       'hours' => 24, // Keep entries for 24 hours
   ],
   ```

3. **Schedule automatic pruning:**
   ```php
   // In app/Console/Kernel.php
   $schedule->command('telescope:prune --hours=24')->daily();
   ```

4. **Use Telescope's sampling:**
   ```php
   // In config/telescope.php
   'sampling' => true,
   ```

### Exception Stack Traces Missing

**Symptom:** Failed tasks don't show complete stack traces

**Explanation:** Stack traces are intentionally limited to 10 frames to prevent database bloat.

**Solutions:**

1. View full exception in Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Use Telescope's Exceptions tab for complete traces

3. Enable verbose logging:
   ```env
   PARALLEL_LOG_LEVEL=debug
   PARALLEL_LOG_TASK_DETAILS=true
   ```

### Memory Leaks with Octane/Swoole

**Symptom:** Memory usage grows continuously in Octane environments

**Cause:** Active task tracking accumulates in long-running processes

**Solutions:**

The integration includes built-in memory leak protection:
- TTL-based cleanup (5-minute expiration)
- Maximum 1000 active tasks
- Automatic garbage collection

If issues persist:

1. Verify TTL cleanup is working:
   ```bash
   # Monitor memory usage
   php artisan octane:status
   ```

2. Reduce task volume or increase worker recycling:
   ```php
   // In config/octane.php
   'max_requests' => 500, // Restart workers more frequently
   ```

---

## Performance Considerations

### Minimal Overhead

The Telescope integration is designed to have negligible performance impact:
- **Recording overhead:** ~0.5-1ms per task
- **Storage overhead:** ~2-5KB per task entry
- **Memory overhead:** <1MB for 1000 active tasks
- Events recorded asynchronously after task completion
- No blocking I/O during task execution

### Production Recommendations

1. **Disable detailed recording for high-volume workloads:**
   ```env
   PARALLEL_TELESCOPE_RECORD_TASKS=false
   ```

2. **Use environment-based configuration:**
   ```php
   // Enable Telescope only in local/staging
   'telescope' => [
       'enabled' => env('APP_ENV') !== 'production',
   ],
   ```

3. **Configure appropriate pruning:**
   ```php
   // config/telescope.php
   'prune' => [
       'hours' => 24, // Shorter retention in production
   ],
   ```

4. **Use sampling for high traffic:**
   ```php
   // config/telescope.php
   'enabled' => env('TELESCOPE_ENABLED', true),
   'sampling' => true,
   'lottery' => [2, 100], // Record 2% of requests
   ```

5. **Monitor database size:**
   ```bash
   # Check Telescope table sizes
   SELECT
       table_name,
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
   FROM information_schema.TABLES
   WHERE table_schema = 'your_database'
   AND table_name LIKE 'telescope_%';
   ```

### Scaling Considerations

For applications processing >1000 tasks/minute:

1. **Use dedicated database for Telescope:**
   ```php
   // config/telescope.php
   'storage' => [
       'database' => [
           'connection' => 'telescope', // Separate connection
       ],
   ],
   ```

2. **Implement aggressive pruning:**
   ```bash
   # Hourly pruning for high-volume
   php artisan telescope:prune --hours=1
   ```

3. **Consider selective recording:**
   ```php
   // Only record failed tasks
   use LaravelParallel\Events\TaskFailed;
   use LaravelParallel\Integrations\Telescope\ParallelTaskWatcher;

   Event::listen(TaskFailed::class, function ($event) {
       app(ParallelTaskWatcher::class)->recordTaskFailure($event);
   });
   ```

4. **Use Redis for temporary storage:**
   - Store high-frequency data in Redis
   - Persist only critical failures to database
   - Aggregate metrics before database writes

### Comparison with Horizon Integration

| Feature | Telescope | Horizon |
|---------|-----------|---------|
| **Purpose** | Debugging & profiling | Metrics & monitoring |
| **Best For** | Development & troubleshooting | Production monitoring |
| **Storage** | Database (persistent) | Redis (ephemeral) |
| **Retention** | Configurable (hours/days) | Recent only (in-memory) |
| **Overhead** | ~0.5-1ms per task | ~0.3-0.5ms per task |
| **Data Detail** | Full task results & traces | Aggregated statistics |
| **UI Focus** | Individual task inspection | Real-time metrics dashboard |
| **Production Use** | Optional (with sampling) | Recommended |

**Recommendation:** Use both integrations for comprehensive observability:
- Telescope for debugging and detailed task inspection
- Horizon for real-time monitoring and performance metrics

---

## Next Steps

- **[Main Documentation](../README.md)** - Full Laravel Parallel documentation
- **[Horizon Integration Guide](./horizon-integration.md)** - Real-time metrics monitoring
- **[Laravel Telescope Documentation](https://laravel.com/docs/telescope)** - Official Telescope guide
- **[Configuration Reference](../config/parallel.php)** - Complete configuration options

---

## Support

If you encounter issues not covered in this guide:

1. Review the troubleshooting section above
2. Check [Laravel Telescope Documentation](https://laravel.com/docs/telescope)
3. Open an issue on [GitHub](https://github.com/giorgospatelis/laravel-parallel/issues)
4. Email support: giorgospatelis@outlook.com
