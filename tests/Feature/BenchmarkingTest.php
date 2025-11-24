<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\BenchmarkResult;
use LaravelParallel\Benchmarking\BenchmarkRunner;
use LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario;
use LaravelParallel\Benchmarking\Scenarios\IoBoundScenario;
use LaravelParallel\Benchmarking\Scenarios\MemoryIntensiveScenario;
use LaravelParallel\Benchmarking\Scenarios\MixedWorkloadScenario;

beforeEach(function () {
    // Enable mock worker pools for testing
    config(['parallel.use_mock_worker_pools' => true]);
});

it('can run complete CPU-bound benchmark', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->scenarioName)->toBe('cpu-bound');
    expect($result->iterations)->toBe(10);
    expect($result->totalTime)->toBeGreaterThan(0);
});

it('can run complete I/O-bound benchmark', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new IoBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->scenarioName)->toBe('io-bound');
    expect($result->iterations)->toBe(10);
    expect($result->totalTime)->toBeGreaterThan(0);
});

it('can run complete mixed workload benchmark', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new MixedWorkloadScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->scenarioName)->toBe('mixed');
    expect($result->iterations)->toBe(10);
    expect($result->totalTime)->toBeGreaterThan(0);
});

it('can run complete memory-intensive benchmark', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new MemoryIntensiveScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->scenarioName)->toBe('memory');
    expect($result->iterations)->toBe(10);
    expect($result->totalTime)->toBeGreaterThan(0);
});

it('can run all benchmarks in sequence', function () {
    $runner = app(BenchmarkRunner::class);
    $scenarios = [
        new CpuBoundScenario,
        new IoBoundScenario,
        new MixedWorkloadScenario,
        new MemoryIntensiveScenario,
    ];

    $results = $runner->runMultiple($scenarios, workerCount: 2, iterations: 5);

    expect($results)->toHaveCount(4);

    foreach ($results as $result) {
        expect($result)->toBeInstanceOf(BenchmarkResult::class);
        expect($result->iterations)->toBe(5);
        expect($result->totalTime)->toBeGreaterThan(0);
    }
});

it('benchmarks are configured correctly', function () {
    $enabled = config('parallel.benchmarking.enabled');
    $defaultIterations = config('parallel.benchmarking.default_iterations');
    $scenarios = config('parallel.benchmarking.scenarios');

    expect($enabled)->toBeTrue();
    expect($defaultIterations)->toBe(100);
    expect($scenarios)->toBeArray();
    expect($scenarios)->toHaveCount(4);
    expect($scenarios)->toHaveKeys(['cpu-bound', 'io-bound', 'mixed', 'memory']);
});

it('benchmark runner is registered in container', function () {
    $runner = app(BenchmarkRunner::class);

    expect($runner)->toBeInstanceOf(BenchmarkRunner::class);

    // Should be singleton
    $runner2 = app(BenchmarkRunner::class);
    expect($runner)->toBe($runner2);
});

it('benchmarks collect accurate performance metrics', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new CpuBoundScenario;

    $start = microtime(true);
    $result = $runner->run($scenario, workerCount: 2, iterations: 20);
    $elapsed = microtime(true) - $start;

    // Result should capture timing accurately
    expect($result->totalTime)->toBeLessThan($elapsed + 0.1);
    expect($result->totalTime)->toBeGreaterThan(0);

    // Metrics should be internally consistent
    expect($result->averageTimePerTask)->toBe($result->totalTime / $result->iterations);
    expect($result->throughput)->toBe($result->iterations / $result->totalTime);
});

it('benchmarks can use different worker counts', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new CpuBoundScenario;

    $result2Workers = $runner->run($scenario, workerCount: 2, iterations: 10);
    $result4Workers = $runner->run($scenario, workerCount: 4, iterations: 10);

    expect($result2Workers->workerCount)->toBe(2);
    expect($result4Workers->workerCount)->toBe(4);
});

it('benchmarks export to array correctly', function () {
    $runner = app(BenchmarkRunner::class);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);
    $array = $result->toArray();

    expect($array)->toHaveKeys([
        'scenario',
        'category',
        'iterations',
        'workers',
        'total_time',
        'average_time',
        'throughput',
        'memory_peak',
    ]);
});
