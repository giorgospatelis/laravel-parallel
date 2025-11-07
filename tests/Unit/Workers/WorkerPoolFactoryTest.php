<?php

declare(strict_types=1);

use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Workers\WorkerConfiguration;
use LaravelParallel\Workers\WorkerPoolFactory;

beforeEach(function () {
    $this->cpuDetector = new CpuDetector();
    $this->factory = new WorkerPoolFactory($this->cpuDetector);
});

it('creates a worker pool with explicit worker count', function () {
    $config = new WorkerConfiguration(workerCount: 4);
    $pool = $this->factory->create($config);

    expect($pool)->toBeInstanceOf(Amp\Parallel\Worker\WorkerPool::class);
});

it('creates a worker pool with auto-detected CPU count', function () {
    CpuDetector::clearCache();
    $config = new WorkerConfiguration(workerCount: 0); // 0 means auto-detect
    $pool = $this->factory->create($config);

    expect($pool)->toBeInstanceOf(Amp\Parallel\Worker\WorkerPool::class);
});

it('creates separate pool instances with different configurations', function () {
    $config1 = new WorkerConfiguration(workerCount: 4);
    $config2 = new WorkerConfiguration(workerCount: 8);

    $pool1 = $this->factory->create($config1);
    $pool2 = $this->factory->create($config2);

    // Each should be a separate pool instance
    expect($pool1)->not->toBe($pool2);

    // Clean up pools
    $pool1->shutdown();
    $pool2->shutdown();
});

it('respects configured worker count', function () {
    $config = new WorkerConfiguration(workerCount: 2);
    $pool = $this->factory->create($config);

    // Verify the pool has the correct worker limit
    expect($pool)->toBeInstanceOf(Amp\Parallel\Worker\ContextWorkerPool::class);
    expect($pool->getLimit())->toBe(2);

    // Clean up
    $pool->shutdown();
});

it('uses auto-detected CPU cores when worker count is 0', function () {
    CpuDetector::clearCache();
    $config = new WorkerConfiguration(workerCount: 0);
    $pool = $this->factory->create($config);

    $cpuCount = (new CpuDetector())->detect();

    // Should use CPU count
    expect($pool->getLimit())->toBe($cpuCount);

    // Clean up
    $pool->shutdown();
});
