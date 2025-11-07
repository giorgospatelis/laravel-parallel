<?php

declare(strict_types=1);

use LaravelParallel\Exceptions\ParallelException;
use LaravelParallel\Support\TaskValidator;

beforeEach(function () {
    $this->validator = new TaskValidator();
});

describe('validateCallables', function () {
    it('accepts an array of callables', function () {
        $tasks = [
            'task1' => fn () => 'result1',
            'task2' => fn () => 'result2',
        ];

        expect(fn () => $this->validator->validateCallables($tasks))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception for non-callable values', function () {
        $tasks = [
            'task1' => fn () => 'result1',
            'task2' => 'not-a-callable',
        ];

        expect(fn () => $this->validator->validateCallables($tasks))
            ->toThrow(ParallelException::class, "Invalid closure provided at key 'task2'");
    });

    it('accepts empty array', function () {
        expect(fn () => $this->validator->validateCallables([]))
            ->not->toThrow(ParallelException::class);
    });
});

describe('validateNotEmpty', function () {
    it('accepts non-empty arrays', function () {
        $tasks = ['task1' => fn () => 'result'];

        expect(fn () => $this->validator->validateNotEmpty($tasks))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception for empty arrays', function () {
        expect(fn () => $this->validator->validateNotEmpty([]))
            ->toThrow(ParallelException::class, 'No closures provided');
    });
});

describe('validateWorkerCount', function () {
    it('accepts valid worker counts', function () {
        expect(fn () => $this->validator->validateWorkerCount(1))
            ->not->toThrow(ParallelException::class);

        expect(fn () => $this->validator->validateWorkerCount(4))
            ->not->toThrow(ParallelException::class);

        expect(fn () => $this->validator->validateWorkerCount(128))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception for worker count below minimum', function () {
        expect(fn () => $this->validator->validateWorkerCount(0))
            ->toThrow(ParallelException::class, 'Invalid worker count');
    });

    it('throws exception for worker count above maximum', function () {
        expect(fn () => $this->validator->validateWorkerCount(129))
            ->toThrow(ParallelException::class, 'Invalid worker count');
    });

    it('accepts custom min and max bounds', function () {
        expect(fn () => $this->validator->validateWorkerCount(5, 2, 10))
            ->not->toThrow(ParallelException::class);

        expect(fn () => $this->validator->validateWorkerCount(1, 2, 10))
            ->toThrow(ParallelException::class);
    });
});

describe('validateTimeout', function () {
    it('accepts positive timeout values', function () {
        expect(fn () => $this->validator->validateTimeout(1.0))
            ->not->toThrow(ParallelException::class);

        expect(fn () => $this->validator->validateTimeout(30.5))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception for zero timeout', function () {
        expect(fn () => $this->validator->validateTimeout(0.0))
            ->toThrow(ParallelException::class, 'Invalid timeout');
    });

    it('throws exception for negative timeout', function () {
        expect(fn () => $this->validator->validateTimeout(-5.0))
            ->toThrow(ParallelException::class, 'Invalid timeout');
    });
});

describe('validateAll', function () {
    it('passes all validations for valid tasks', function () {
        $tasks = [
            'task1' => fn () => 'result1',
            'task2' => fn () => 'result2',
        ];

        expect(fn () => $this->validator->validateAll($tasks))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception for empty tasks', function () {
        expect(fn () => $this->validator->validateAll([]))
            ->toThrow(ParallelException::class, 'No closures provided');
    });

    it('throws exception for non-callable tasks', function () {
        $tasks = [
            'task1' => fn () => 'result1',
            'task2' => 'invalid',
        ];

        expect(fn () => $this->validator->validateAll($tasks))
            ->toThrow(ParallelException::class, 'Invalid closure');
    });
});

describe('validateTaskCount', function () {
    it('accepts task count within limits', function () {
        config(['parallel.max_tasks_per_batch' => 100]);
        config(['parallel.auto_chunk' => false]);

        expect(fn () => $this->validator->validateTaskCount(50))
            ->not->toThrow(ParallelException::class);
    });

    it('throws exception when exceeding max tasks with auto-chunk disabled', function () {
        config(['parallel.max_tasks_per_batch' => 100]);
        config(['parallel.auto_chunk' => false]);

        expect(fn () => $this->validator->validateTaskCount(150))
            ->toThrow(ParallelException::class, 'Too many tasks submitted');
    });

    it('allows exceeding max tasks when auto-chunk is enabled', function () {
        config(['parallel.max_tasks_per_batch' => 100]);
        config(['parallel.auto_chunk' => true]);

        expect(fn () => $this->validator->validateTaskCount(150))
            ->not->toThrow(ParallelException::class);
    });

    it('allows unlimited tasks when max_tasks_per_batch is 0', function () {
        config(['parallel.max_tasks_per_batch' => 0]);
        config(['parallel.auto_chunk' => false]);

        expect(fn () => $this->validator->validateTaskCount(100000))
            ->not->toThrow(ParallelException::class);
    });

    it('allows unlimited tasks when max_tasks_per_batch is negative', function () {
        config(['parallel.max_tasks_per_batch' => -1]);
        config(['parallel.auto_chunk' => false]);

        expect(fn () => $this->validator->validateTaskCount(100000))
            ->not->toThrow(ParallelException::class);
    });
});
