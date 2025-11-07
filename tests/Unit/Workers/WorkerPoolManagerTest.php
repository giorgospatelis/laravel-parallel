<?php

declare(strict_types=1);

use LaravelParallel\Workers\WorkerPoolManager;

beforeEach(function () {
    // Use a synchronous mock worker pool to avoid spawning real processes
    // This makes tests compatible with code coverage tools like PCOV
    $pool = $this->createTestWorkerPool(4);

    $this->manager = new WorkerPoolManager($pool, 4);
});

afterEach(function () {
    // Ensure worker pool is shut down after each test
    // This prevents orphaned processes from blocking code coverage collection
    if (isset($this->manager) && $this->manager->isRunning()) {
        $this->manager->shutdown();
    }
});

it('reports the correct worker count', function () {
    expect($this->manager->getWorkerCount())->toBe(4);
});

it('is initially running', function () {
    expect($this->manager->isRunning())->toBeTrue();
});

it('can be shutdown', function () {
    expect($this->manager->isRunning())->toBeTrue();

    $this->manager->shutdown();

    expect($this->manager->isRunning())->toBeFalse();
});

it('can safely shutdown multiple times', function () {
    $this->manager->shutdown();
    $this->manager->shutdown(); // Should not error

    expect($this->manager->isRunning())->toBeFalse();
});

it('implements WorkerPoolContract', function () {
    expect($this->manager)->toBeInstanceOf(LaravelParallel\Contracts\WorkerPoolContract::class);
});

it('can submit tasks to pool', function () {
    $task = new LaravelParallel\Tasks\ClosureTask(fn () => 'test result');

    $execution = $this->manager->submit($task);

    expect($execution)->toBeInstanceOf(Amp\Parallel\Worker\Execution::class);
});
