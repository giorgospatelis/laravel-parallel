<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Workers\WorkerPoolFactory;

beforeEach(function () {
    $this->cpuDetector = new CpuDetector();
    $this->poolFactory = new WorkerPoolFactory($this->cpuDetector);
    $this->resultCollector = new ResultCollector();
    $this->validator = new TaskValidator();

    $this->executor = new Executor(
        $this->poolFactory,
        $this->resultCollector,
        $this->validator
    );
});

it('handles empty task array', function () {
    $results = $this->executor->execute([]);

    expect($results)->toBeEmpty();
});

it('can set and get timeout', function () {
    expect($this->executor->getTimeout())->toBeNull();

    $this->executor->setTimeout(30.0);

    expect($this->executor->getTimeout())->toBe(30.0);
});

it('can set and get worker count', function () {
    expect($this->executor->getWorkerCount())->toBeNull();

    $this->executor->setWorkerCount(4);

    expect($this->executor->getWorkerCount())->toBe(4);
});

it('implements ExecutorContract', function () {
    expect($this->executor)->toBeInstanceOf(LaravelParallel\Contracts\ExecutorContract::class);
});

it('validates tasks before execution', function () {
    $invalidTasks = [
        'task1' => 'not-a-callable',
    ];

    expect(fn () => $this->executor->execute($invalidTasks))
        ->toThrow(LaravelParallel\Exceptions\ParallelException::class);
});
