# Performance Benchmarking Suite

Laravel Parallel includes a comprehensive benchmarking suite to measure parallel processing performance across different workload types and configurations.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Available Scenarios](#available-scenarios)
- [Running Benchmarks](#running-benchmarks)
- [Interpreting Results](#interpreting-results)
- [Exporting Results](#exporting-results)
- [Creating Custom Scenarios](#creating-custom-scenarios)
- [CI/CD Integration](#cicd-integration)
- [Configuration](#configuration)

## Overview

The benchmarking suite helps you:

- **Measure Performance**: Quantify parallel processing benefits for different workload types
- **Optimize Configuration**: Find the optimal worker count for your hardware and workload
- **Track Regression**: Monitor performance changes over time
- **Compare Approaches**: Evaluate parallel vs sequential execution
- **Document Performance**: Generate reports for stakeholders

## Quick Start

Run all benchmark scenarios:

```bash
php artisan parallel:benchmark all --iterations=100
```

Run a specific scenario:

```bash
php artisan parallel:benchmark cpu-bound --workers=4 --iterations=100
```

List available scenarios:

```bash
php artisan parallel:benchmark
```

## Available Scenarios

### CPU-Bound Workload

**Scenario:** `cpu-bound`

Tests CPU-intensive operations:
- Prime number calculations
- Fibonacci sequence generation
- Cryptographic hashing (SHA-256)

**Best For:**
- Data processing applications
- Mathematical computations
- Algorithm performance testing

**Example:**
```bash
php artisan parallel:benchmark cpu-bound --workers=4 --iterations=100
```

### I/O-Bound Workload

**Scenario:** `io-bound`

Tests I/O-intensive operations:
- File read/write operations
- Simulated network requests
- Disk I/O patterns

**Best For:**
- Applications with heavy file operations
- API-heavy workloads
- Database-intensive tasks

**Example:**
```bash
php artisan parallel:benchmark io-bound --workers=8 --iterations=100
```

### Mixed Workload

**Scenario:** `mixed`

Tests realistic mixed operations:
- Combined CPU and I/O operations
- Data fetching and processing
- Transformation pipelines

**Best For:**
- Real-world application simulation
- End-to-end workflow testing
- Production workload estimation

**Example:**
```bash
php artisan parallel:benchmark mixed --workers=4 --iterations=100
```

### Memory-Intensive Workload

**Scenario:** `memory`

Tests memory-intensive operations:
- Large array manipulations
- String processing on large texts
- Memory allocation patterns

**Best For:**
- Data-heavy applications
- Memory optimization testing
- Resource usage analysis

**Example:**
```bash
php artisan parallel:benchmark memory --workers=2 --iterations=50
```

## Running Benchmarks

### Basic Usage

List available scenarios:
```bash
php artisan parallel:benchmark
```

Run specific scenario:
```bash
php artisan parallel:benchmark {scenario}
```

Run all scenarios:
```bash
php artisan parallel:benchmark all
```

### Options

#### Worker Count

Specify number of parallel workers:
```bash
php artisan parallel:benchmark cpu-bound --workers=4
```

Auto-detect CPU cores (default):
```bash
php artisan parallel:benchmark cpu-bound
```

#### Iterations

Set number of tasks per scenario:
```bash
php artisan parallel:benchmark cpu-bound --iterations=200
```

Higher iterations provide more accurate results but take longer.

**Recommendations:**
- Quick test: `--iterations=10`
- Standard: `--iterations=100`
- Accurate: `--iterations=500`
- Production benchmark: `--iterations=1000`

#### Export Format

Export results for analysis:
```bash
# JSON format
php artisan parallel:benchmark all --export=json

# CSV format
php artisan parallel:benchmark all --export=csv

# Markdown format
php artisan parallel:benchmark all --export=markdown
```

## Interpreting Results

### Metrics Explained

#### Total Time
Total wall-clock time to complete all tasks.

**What it means:**
- Lower is better
- Includes overhead and scheduling
- Real-world execution time

#### Average Time Per Task
Mean execution time for a single task.

**What it means:**
- `Total Time / Iterations`
- Useful for capacity planning
- Expressed in milliseconds

#### Throughput
Tasks processed per second.

**What it means:**
- `Iterations / Total Time`
- Higher is better
- Indicates processing capacity

#### Memory Peak
Peak memory usage during benchmark execution.

**What it means:**
- Memory overhead of parallel processing
- Useful for resource planning
- Expressed in MB/GB

### Performance Analysis

#### Speedup Factor

Calculate parallel speedup:

```
Speedup = Sequential Time / Parallel Time
```

**Example:**
- Sequential: 10 seconds
- Parallel (4 workers): 3 seconds
- Speedup: 10 / 3 = 3.33x

**Ideal Speedup:** 4x for 4 workers (rarely achieved due to overhead)

#### Efficiency

Calculate parallel efficiency:

```
Efficiency = (Speedup / Worker Count) × 100%
```

**Example:**
- Speedup: 3.33x
- Workers: 4
- Efficiency: (3.33 / 4) × 100% = 83%

**Good Efficiency:** > 75%

### Optimal Worker Count

Find the best worker count for your workload:

1. Run benchmarks with different worker counts:
```bash
php artisan parallel:benchmark cpu-bound --workers=2 --iterations=100
php artisan parallel:benchmark cpu-bound --workers=4 --iterations=100
php artisan parallel:benchmark cpu-bound --workers=8 --iterations=100
```

2. Compare throughput and efficiency

3. Choose configuration with:
   - Highest throughput
   - Efficiency > 75%
   - Acceptable memory usage

**General Guidelines:**
- **CPU-bound**: Workers = CPU cores
- **I/O-bound**: Workers = 2-3 × CPU cores
- **Mixed**: Workers = CPU cores + 1

## Exporting Results

### JSON Export

Best for programmatic analysis and CI/CD integration:

```bash
php artisan parallel:benchmark all --export=json
```

**Output:** `benchmark_YYYY-MM-DD_HHmmss.json`

**Structure:**
```json
{
  "timestamp": "2025-11-24T10:30:00+00:00",
  "results": [
    {
      "scenario": "cpu-bound",
      "category": "cpu-bound",
      "iterations": 100,
      "workers": 4,
      "total_time": 5.5678,
      "average_time": 0.055678,
      "throughput": 17.96,
      "memory_peak": 10485760
    }
  ]
}
```

### CSV Export

Best for spreadsheet analysis:

```bash
php artisan parallel:benchmark all --export=csv
```

**Output:** `benchmark_YYYY-MM-DD_HHmmss.csv`

Import into Excel, Google Sheets, or other tools for visualization.

### Markdown Export

Best for documentation and reports:

```bash
php artisan parallel:benchmark all --export=markdown
```

**Output:** `benchmark_YYYY-MM-DD_HHmmss.markdown`

Include in README files, documentation, or GitHub wikis.

## Creating Custom Scenarios

### Step 1: Implement BenchmarkScenario Interface

Create a new scenario class:

```php
<?php

namespace App\Benchmarking;

use LaravelParallel\Contracts\BenchmarkScenario;

final readonly class DatabaseQueryScenario implements BenchmarkScenario
{
    public function getName(): string
    {
        return 'database-query';
    }

    public function getDescription(): string
    {
        return 'Database query performance benchmark';
    }

    public function getCategory(): string
    {
        return 'io-bound';
    }

    public function getTasks(int $iterations): array
    {
        $tasks = [];

        for ($i = 0; $i < $iterations; $i++) {
            $tasks["query_{$i}"] = function () {
                // Recreate connection inside worker
                return \DB::table('users')
                    ->where('active', true)
                    ->count();
            };
        }

        return $tasks;
    }
}
```

### Step 2: Register Custom Scenario

Add to `config/parallel.php`:

```php
'benchmarking' => [
    'scenarios' => [
        'cpu-bound' => \LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario::class,
        'io-bound' => \LaravelParallel\Benchmarking\Scenarios\IoBoundScenario::class,
        'mixed' => \LaravelParallel\Benchmarking\Scenarios\MixedWorkloadScenario::class,
        'memory' => \LaravelParallel\Benchmarking\Scenarios\MemoryIntensiveScenario::class,
        'database-query' => \App\Benchmarking\DatabaseQueryScenario::class,
    ],
],
```

### Step 3: Run Custom Scenario

```bash
php artisan parallel:benchmark database-query --workers=4 --iterations=100
```

### Best Practices for Custom Scenarios

**1. Avoid Serialization Issues**

```php
// ❌ Bad - DB connection won't serialize
$db = DB::connection();
$tasks["query_{$i}"] = fn() => $db->select(...);

// ✅ Good - Recreate connection in worker
$tasks["query_{$i}"] = fn() => DB::connection()->select(...);
```

**2. Ensure Repeatability**

Each task should produce consistent results:

```php
// ✅ Good - Deterministic
$tasks["task_{$i}"] = fn() => hash('sha256', "data_{$i}");

// ⚠️ Caution - Non-deterministic
$tasks["task_{$i}"] = fn() => microtime(true);
```

**3. Clean Up Resources**

```php
$tasks["file_{$i}"] = function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'bench');

    try {
        // Perform operations
        return file_put_contents($tempFile, $data);
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
};
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Performance Benchmarks

on:
  push:
    branches: [main]
  pull_request:

jobs:
  benchmark:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install --no-dev --prefer-dist

      - name: Run Benchmarks
        run: php artisan parallel:benchmark all --export=json --iterations=100

      - name: Upload Results
        uses: actions/upload-artifact@v3
        with:
          name: benchmark-results
          path: benchmark_*.json
```

### Performance Regression Detection

```bash
# Run benchmark and save results
php artisan parallel:benchmark cpu-bound --export=json > results_new.json

# Compare with baseline
# (Use jq or custom script to compare throughput)

# Fail if regression > 10%
if [ "$REGRESSION" -gt 10 ]; then
  echo "Performance regression detected!"
  exit 1
fi
```

## Configuration

Configure benchmarking in `config/parallel.php`:

```php
'benchmarking' => [
    // Enable/disable benchmarking suite
    'enabled' => env('PARALLEL_BENCHMARKING_ENABLED', true),

    // Default iterations per scenario
    'default_iterations' => env('PARALLEL_BENCHMARK_ITERATIONS', 100),

    // Default worker count (null = auto-detect)
    'default_workers' => env('PARALLEL_BENCHMARK_WORKERS', null),

    // Available scenarios
    'scenarios' => [
        'cpu-bound' => \LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario::class,
        'io-bound' => \LaravelParallel\Benchmarking\Scenarios\IoBoundScenario::class,
        'mixed' => \LaravelParallel\Benchmarking\Scenarios\MixedWorkloadScenario::class,
        'memory' => \LaravelParallel\Benchmarking\Scenarios\MemoryIntensiveScenario::class,
    ],
],
```

### Environment Variables

```env
# Enable benchmarking
PARALLEL_BENCHMARKING_ENABLED=true

# Default iterations
PARALLEL_BENCHMARK_ITERATIONS=100

# Default worker count (null = auto-detect)
PARALLEL_BENCHMARK_WORKERS=4
```

## Troubleshooting

### Benchmarks Take Too Long

**Solution:** Reduce iterations:
```bash
php artisan parallel:benchmark cpu-bound --iterations=10
```

### Memory Errors

**Solution:** Reduce worker count or iterations:
```bash
php artisan parallel:benchmark memory --workers=2 --iterations=50
```

### Inconsistent Results

**Causes:**
- System load interference
- Background processes
- Thermal throttling

**Solutions:**
- Run on dedicated hardware
- Increase iterations for averaging
- Close unnecessary applications

### Command Not Found

**Solution:** Ensure package is properly installed:
```bash
composer require laravel-parallel/laravel-parallel
php artisan config:clear
```

## Performance Tips

1. **Run Multiple Times**: Average 3-5 runs for accurate results
2. **Warm Up**: First run may be slower due to PHP OpCache
3. **Consistent Environment**: Same hardware, load, and configuration
4. **Monitor Resources**: Watch CPU, memory, and I/O during benchmarks
5. **Document Conditions**: Record hardware specs, PHP version, and system load

## Examples

### Finding Optimal Configuration

```bash
# Test different worker counts
for workers in 2 4 8 16; do
  php artisan parallel:benchmark cpu-bound \
    --workers=$workers \
    --iterations=100 \
    --export=json
done

# Compare results and choose optimal configuration
```

### Production Performance Testing

```bash
# Run comprehensive benchmark
php artisan parallel:benchmark all \
  --iterations=500 \
  --export=markdown

# Include results in performance report
```

### Regression Testing

```bash
# Before code changes
php artisan parallel:benchmark all --export=json > baseline.json

# After code changes
php artisan parallel:benchmark all --export=json > current.json

# Compare results (custom script)
./scripts/compare-benchmarks.sh baseline.json current.json
```

## Further Reading

- [Main Documentation](../README.md)
- [Configuration Guide](../README.md#configuration)
- [Performance Guidelines](../README.md#performance-guidelines)
- [Laravel Horizon Integration](horizon-integration.md)
- [Laravel Telescope Integration](telescope-integration.md)

## Support

For questions, issues, or feature requests:
- GitHub Issues: [laravel-parallel/laravel-parallel](https://github.com/laravel-parallel/laravel-parallel/issues)
- Documentation: [README.md](../README.md)
