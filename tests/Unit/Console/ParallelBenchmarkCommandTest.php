<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Enable mock worker pools for testing
    config(['parallel.use_mock_worker_pools' => true]);
});

it('lists available scenarios when no argument provided', function () {
    Artisan::call('parallel:benchmark');

    $output = Artisan::output();

    expect($output)->toContain('Available Benchmark Scenarios');
    expect($output)->toContain('cpu-bound');
    expect($output)->toContain('io-bound');
    expect($output)->toContain('mixed');
    expect($output)->toContain('memory');
});

it('runs a specific scenario', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
        '--workers' => 2,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Running benchmarks');
    expect($output)->toContain('CPU-intensive tasks');
    expect($output)->toContain('Completed');
});

it('runs all scenarios', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'all',
        '--iterations' => 3,
        '--workers' => 2,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Running benchmarks');
    expect($output)->toContain('CPU-intensive tasks');
    expect($output)->toContain('I/O-intensive tasks');
    expect($output)->toContain('Mixed workload');
    expect($output)->toContain('Memory-intensive tasks');
});

it('displays results in table format', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
        '--workers' => 2,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Benchmark Results');
    expect($output)->toContain('Scenario');
    expect($output)->toContain('Workers');
    expect($output)->toContain('Throughput');
});

it('exports results as JSON', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
        '--workers' => 2,
        '--export' => 'json',
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Results exported to:');
    expect($output)->toContain('.json');

    // Clean up exported file
    $files = glob('benchmark_*.json');
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('exports results as CSV', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
        '--workers' => 2,
        '--export' => 'csv',
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Results exported to:');
    expect($output)->toContain('.csv');

    // Clean up exported file
    $files = glob('benchmark_*.csv');
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('exports results as Markdown', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
        '--workers' => 2,
        '--export' => 'markdown',
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Results exported to:');
    expect($output)->toContain('.markdown');

    // Clean up exported file
    $files = glob('benchmark_*.markdown');
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('fails with invalid scenario name', function () {
    $exitCode = Artisan::call('parallel:benchmark', [
        'scenario' => 'invalid-scenario',
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Unknown scenario');
});

it('fails with invalid iterations', function () {
    $exitCode = Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 0,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Iterations must be at least 1');
});

it('fails with invalid worker count', function () {
    $exitCode = Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--workers' => 0,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Worker count must be at least 1');
});

it('fails when benchmarking is disabled', function () {
    config(['parallel.benchmarking.enabled' => false]);

    $exitCode = Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Benchmarking is disabled');
});

it('uses auto-detect for worker count when not specified', function () {
    Artisan::call('parallel:benchmark', [
        'scenario' => 'cpu-bound',
        '--iterations' => 5,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Workers: auto-detect');
});

it('command is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('parallel:benchmark');
});
