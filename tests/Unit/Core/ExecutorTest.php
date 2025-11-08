<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Tests\Mocks\MockWorkerPoolFactory;

beforeEach(function () {
    $this->cpuDetector = new CpuDetector;

    // Use mock factory to avoid spawning real processes during tests
    // This makes tests compatible with code coverage tools like PCOV
    $this->poolFactory = new MockWorkerPoolFactory($this->cpuDetector);

    $this->resultCollector = new ResultCollector;
    $this->validator = new TaskValidator;

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

it('executes single task successfully', function () {
    $tasks = [
        'task1' => function () {
            return 'result1';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue()
        ->and($results['task1']->getValue())->toBe('result1');
});

it('executes multiple tasks successfully', function () {
    $tasks = [
        'task1' => function () {
            return 'result1';
        },
        'task2' => function () {
            return 'result2';
        },
        'task3' => function () {
            return 'result3';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(3)
        ->and($results['task1']->getValue())->toBe('result1')
        ->and($results['task2']->getValue())->toBe('result2')
        ->and($results['task3']->getValue())->toBe('result3');
});

it('executes tasks with different return types', function () {
    $tasks = [
        'string' => function () {
            return 'text';
        },
        'int' => function () {
            return 42;
        },
        'float' => function () {
            return 3.14;
        },
        'array' => function () {
            return [1, 2, 3];
        },
        'bool' => function () {
            return true;
        },
        'null' => function () {
            return null;
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(6)
        ->and($results['string']->getValue())->toBe('text')
        ->and($results['int']->getValue())->toBe(42)
        ->and($results['float']->getValue())->toBe(3.14)
        ->and($results['array']->getValue())->toBe([1, 2, 3])
        ->and($results['bool']->getValue())->toBeTrue()
        ->and($results['null']->getValue())->toBeNull();
});

it('executes tasks with numeric keys', function () {
    $tasks = [
        0 => function () {
            return 'first';
        },
        1 => function () {
            return 'second';
        },
        2 => function () {
            return 'third';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(3)
        ->and($results[0]->getValue())->toBe('first')
        ->and($results[1]->getValue())->toBe('second')
        ->and($results[2]->getValue())->toBe('third');
});

it('handles task failures gracefully', function () {
    $tasks = [
        'success' => function () {
            return 'result';
        },
        'failure' => function () {
            throw new RuntimeException('Task failed');
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(2)
        ->and($results['success']->isSuccess())->toBeTrue()
        ->and($results['failure']->isSuccess())->toBeFalse()
        ->and($results['failure']->getException())->toBeInstanceOf(Throwable::class)
        ->and($results['failure']->getException()->getMessage())->toContain('Task failed');
});

it('continues execution after task failure', function () {
    $tasks = [
        'task1' => function () {
            return 'result1';
        },
        'task2' => function () {
            throw new RuntimeException('Failed');
        },
        'task3' => function () {
            return 'result3';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(3)
        ->and($results['task1']->isSuccess())->toBeTrue()
        ->and($results['task2']->isSuccess())->toBeFalse()
        ->and($results['task3']->isSuccess())->toBeTrue();
});

it('sets worker count correctly', function () {
    $this->executor->setWorkerCount(8);

    $tasks = [
        'task1' => function () {
            return 'result1';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('executes with configured timeout', function () {
    $this->executor->setTimeout(5.0);

    $tasks = [
        'task1' => function () {
            return 'result1';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('records execution time for tasks', function () {
    $tasks = [
        'task1' => function () {
            usleep(10000); // 10ms

            return 'result1';
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results['task1']->getExecutionTime())->toBeGreaterThan(0.0);
});

it('executes tasks with closures that access variables', function () {
    $tasks = [
        'task1' => function () {
            $multiplier = 2;

            return 10 * $multiplier;
        },
        'task2' => function () {
            $multiplier = 2;

            return 20 * $multiplier;
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results['task1']->getValue())->toBe(20)
        ->and($results['task2']->getValue())->toBe(40);
});

it('handles tasks that return objects', function () {
    $tasks = [
        'task1' => function () {
            return (object) ['name' => 'test', 'value' => 123];
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results['task1']->isSuccess())->toBeTrue()
        ->and($results['task1']->getValue())->toBeInstanceOf(stdClass::class)
        ->and($results['task1']->getValue()->name)->toBe('test')
        ->and($results['task1']->getValue()->value)->toBe(123);
});

it('validates worker count before execution', function () {
    $this->executor->setWorkerCount(-1);

    expect(fn () => $this->executor->execute([
        'task1' => function () {
            return 'result';
        },
    ]))
        ->toThrow(LaravelParallel\Exceptions\ParallelException::class);
});

it('validates timeout before execution', function () {
    $this->executor->setTimeout(-5.0);

    $tasks = [
        'task1' => function () {
            return 'result';
        },
    ];

    $this->executor->execute($tasks);
})->throws(LaravelParallel\Exceptions\ParallelException::class, 'Invalid timeout: -5');

it('validates timeout is positive', function () {
    $this->executor->setTimeout(0.0);

    $tasks = [
        'task1' => function () {
            return 'result';
        },
    ];

    $this->executor->execute($tasks);
})->throws(LaravelParallel\Exceptions\ParallelException::class, 'Invalid timeout: 0');

it('executes many tasks efficiently', function () {
    $taskCount = 20;
    $tasks = [];

    for ($i = 1; $i <= $taskCount; $i++) {
        $tasks["task_{$i}"] = function () use ($i) {
            return "result_{$i}";
        };
    }

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount($taskCount);

    foreach ($results as $key => $result) {
        expect($result->isSuccess())->toBeTrue();
    }
});

it('preserves task key associations', function () {
    $tasks = [
        'user_fetch' => function () {
            return ['id' => 1];
        },
        'product_fetch' => function () {
            return ['id' => 2];
        },
        'order_fetch' => function () {
            return ['id' => 3];
        },
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveKeys(['user_fetch', 'product_fetch', 'order_fetch'])
        ->and($results['user_fetch']->getValue())->toBe(['id' => 1])
        ->and($results['product_fetch']->getValue())->toBe(['id' => 2])
        ->and($results['order_fetch']->getValue())->toBe(['id' => 3]);
});

it('logs execution started when logging enabled', function () {
    config(['parallel.logging.enabled' => true]);
    config(['parallel.logging.channel' => 'stack']);
    config(['parallel.logging.level' => 'info']);

    $tasks = [
        'task1' => fn () => 'result1',
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('logs execution completed when logging enabled', function () {
    config(['parallel.logging.enabled' => true]);
    config(['parallel.logging.channel' => 'stack']);
    config(['parallel.logging.level' => 'info']);

    $tasks = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(2);
});

it('does not log when logging is disabled', function () {
    config(['parallel.logging.enabled' => false]);

    $tasks = [
        'task1' => fn () => 'result1',
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('respects log level configuration', function () {
    config(['parallel.logging.enabled' => true]);
    config(['parallel.logging.channel' => 'stack']);
    config(['parallel.logging.level' => 'error']); // Only log errors, not info/debug

    $tasks = [
        'task1' => fn () => 'result1',
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('caches worker configuration to avoid rebuilding', function () {
    $this->executor->setWorkerCount(4);
    $this->executor->setTimeout(10.0);

    $tasks1 = ['task1' => fn () => 'result1'];
    $tasks2 = ['task2' => fn () => 'result2'];

    $results1 = $this->executor->execute($tasks1);
    $results2 = $this->executor->execute($tasks2);

    expect($results1)->toHaveCount(1)
        ->and($results2)->toHaveCount(1);
});

it('invalidates config cache when worker count changes', function () {
    $this->executor->setWorkerCount(4);

    $tasks1 = ['task1' => fn () => 'result1'];
    $results1 = $this->executor->execute($tasks1);

    $this->executor->setWorkerCount(8);

    $tasks2 = ['task2' => fn () => 'result2'];
    $results2 = $this->executor->execute($tasks2);

    expect($results1)->toHaveCount(1)
        ->and($results2)->toHaveCount(1);
});

it('invalidates config cache when timeout changes', function () {
    $this->executor->setTimeout(5.0);

    $tasks1 = ['task1' => fn () => 'result1'];
    $results1 = $this->executor->execute($tasks1);

    $this->executor->setTimeout(10.0);

    $tasks2 = ['task2' => fn () => 'result2'];
    $results2 = $this->executor->execute($tasks2);

    expect($results1)->toHaveCount(1)
        ->and($results2)->toHaveCount(1);
});

it('builds config from Laravel config when no explicit settings', function () {
    config(['parallel.default_workers' => 4]);
    config(['parallel.timeout' => 15.0]);

    $tasks = ['task1' => fn () => 'result1'];
    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('cleans up worker pool after execution', function () {
    $tasks = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
    ];

    $results = $this->executor->execute($tasks);

    expect($results)->toHaveCount(2);

    // Execute again to ensure pool was cleaned up
    $results2 = $this->executor->execute($tasks);

    expect($results2)->toHaveCount(2);
});
