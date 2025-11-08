<?php

declare(strict_types=1);

use LaravelParallel\Exceptions\ParallelException;
use LaravelParallel\Exceptions\SerializationException;
use LaravelParallel\Exceptions\TaskException;
use LaravelParallel\Exceptions\TimeoutException;
use LaravelParallel\Exceptions\WorkerPoolException;

describe('TaskException', function () {
    it('extends ParallelException', function () {
        $exception = TaskException::taskNotFound('task1');

        expect($exception)->toBeInstanceOf(ParallelException::class);
    });

    it('creates serialization failed exception', function () {
        $previous = new RuntimeException('Serialization error');
        $exception = TaskException::serializationFailed('task1', $previous);

        expect($exception->getMessage())->toContain('task1')
            ->toContain('serialize')
            ->and($exception->getPrevious())->toBe($previous);
    });

    it('creates invalid task type exception', function () {
        $exception = TaskException::invalidTaskType('ClosureTask', 'ArrayTask');

        expect($exception->getMessage())->toContain('ClosureTask')
            ->toContain('ArrayTask');
    });

    it('creates task not found exception', function () {
        $exception = TaskException::taskNotFound('task_123');

        expect($exception->getMessage())->toContain('task_123')
            ->toContain('not found');
    });
});

describe('WorkerPoolException', function () {
    it('extends ParallelException', function () {
        $exception = WorkerPoolException::poolShutdown();

        expect($exception)->toBeInstanceOf(ParallelException::class);
    });

    it('creates pool shutdown exception', function () {
        $exception = WorkerPoolException::poolShutdown();

        expect($exception->getMessage())->toContain('shut down');
    });

    it('creates worker limit exceeded exception', function () {
        $exception = WorkerPoolException::workerLimitExceeded(200, 128);

        expect($exception->getMessage())->toContain('200')
            ->toContain('128')
            ->toContain('limit');
    });

    it('creates worker crashed exception', function () {
        $previous = new RuntimeException('Crash');
        $exception = WorkerPoolException::workerCrashed(5, $previous);

        expect($exception->getMessage())->toContain('5')
            ->toContain('crashed')
            ->and($exception->getPrevious())->toBe($previous);
    });

    it('creates initialization failed exception', function () {
        $previous = new RuntimeException('Init error');
        $exception = WorkerPoolException::initializationFailed($previous);

        expect($exception->getMessage())->toContain('initialize')
            ->and($exception->getPrevious())->toBe($previous);
    });
});

describe('TimeoutException', function () {
    it('extends ParallelException', function () {
        $exception = TimeoutException::taskTimedOut('task1', 30.0);

        expect($exception)->toBeInstanceOf(ParallelException::class);
    });

    it('creates task timed out exception', function () {
        $exception = TimeoutException::taskTimedOut('task1', 30.0);

        expect($exception->getMessage())->toContain('task1')
            ->toContain('30')
            ->toContain('timed out');
    });

    it('creates pool operation timed out exception', function () {
        $exception = TimeoutException::poolOperationTimedOut('submit', 10.0);

        expect($exception->getMessage())->toContain('submit')
            ->toContain('10')
            ->toContain('timed out');
    });
});

describe('SerializationException', function () {
    it('extends ParallelException', function () {
        $exception = SerializationException::unsupportedType('resource');

        expect($exception)->toBeInstanceOf(ParallelException::class);
    });

    it('creates closure not serializable exception', function () {
        $previous = new RuntimeException('Cannot serialize');
        $exception = SerializationException::closureNotSerializable($previous);

        expect($exception->getMessage())->toContain('Closure')
            ->toContain('serializable')
            ->and($exception->getPrevious())->toBe($previous);
    });

    it('creates unsupported type exception', function () {
        $exception = SerializationException::unsupportedType('resource');

        expect($exception->getMessage())->toContain('resource')
            ->toContain('type');
    });

    it('creates unserialization failed exception', function () {
        $previous = new RuntimeException('Bad data');
        $exception = SerializationException::unserializationFailed($previous);

        expect($exception->getMessage())->toContain('unserialize')
            ->and($exception->getPrevious())->toBe($previous);
    });

    it('creates context not available exception', function () {
        $exception = SerializationException::contextNotAvailable('database');

        expect($exception->getMessage())->toContain('database')
            ->toContain('not available');
    });
});
