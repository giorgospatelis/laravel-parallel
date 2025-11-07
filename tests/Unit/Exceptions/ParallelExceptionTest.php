<?php

declare(strict_types=1);

use LaravelParallel\Exceptions\ParallelException;

it('creates cpu detection failed exception', function () {
    $exception = ParallelException::cpuDetectionFailed();

    expect($exception)->toBeInstanceOf(ParallelException::class)
        ->and($exception->getMessage())->toContain('CPU core count');
});

it('creates empty closures exception', function () {
    $exception = ParallelException::emptyClosures();

    expect($exception->getMessage())->toContain('No closures provided');
});

it('creates invalid closure exception', function () {
    $exception = ParallelException::invalidClosure('task1');

    expect($exception->getMessage())->toContain('task1')
        ->toContain('callable');
});

it('creates invalid timeout exception', function () {
    $exception = ParallelException::invalidTimeout(-5.0);

    expect($exception->getMessage())->toContain('-5')
        ->toContain('timeout');
});

it('creates invalid worker count exception', function () {
    $exception = ParallelException::invalidWorkerCount(200, 128);

    expect($exception->getMessage())->toContain('200')
        ->toContain('128');
});

it('creates task execution failed exception', function () {
    $previous = new RuntimeException('Task error');
    $exception = ParallelException::taskExecutionFailed('task1', $previous);

    expect($exception->getMessage())->toContain('task1')
        ->toContain('Task error')
        ->and($exception->getPrevious())->toBe($previous);
});

it('creates worker pool creation failed exception', function () {
    $previous = new RuntimeException('Pool error');
    $exception = ParallelException::workerPoolCreationFailed($previous);

    expect($exception->getMessage())->toContain('worker pool')
        ->toContain('Pool error')
        ->and($exception->getPrevious())->toBe($previous);
});

it('creates too many tasks exception', function () {
    $exception = ParallelException::tooManyTasks(15000, 10000);

    expect($exception->getMessage())
        ->toContain('15000')
        ->toContain('10000')
        ->toContain('Too many tasks')
        ->toContain('auto-chunking');
});

it('can be constructed with message only', function () {
    $exception = new ParallelException('Test message');

    expect($exception->getMessage())->toBe('Test message')
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('can be constructed with message and code', function () {
    $exception = new ParallelException('Test message', 42);

    expect($exception->getMessage())->toBe('Test message')
        ->and($exception->getCode())->toBe(42)
        ->and($exception->getPrevious())->toBeNull();
});

it('can be constructed with message code and previous', function () {
    $previous = new RuntimeException('Previous error');
    $exception = new ParallelException('Test message', 42, $previous);

    expect($exception->getMessage())->toBe('Test message')
        ->and($exception->getCode())->toBe(42)
        ->and($exception->getPrevious())->toBe($previous);
});

it('extends RuntimeException', function () {
    $exception = new ParallelException('Test');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
