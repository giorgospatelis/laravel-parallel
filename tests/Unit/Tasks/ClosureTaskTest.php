<?php

declare(strict_types=1);

use LaravelParallel\Tasks\ClosureTask;

it('creates a task from a closure', function () {
    $closure = fn () => 'test result';
    $task = new ClosureTask($closure);

    expect($task)->toBeInstanceOf(ClosureTask::class);
});

it('has a unique identifier', function () {
    $task1 = new ClosureTask(fn () => 1);
    $task2 = new ClosureTask(fn () => 2);

    expect($task1->getId())->toBeString()
        ->and($task2->getId())->toBeString()
        ->and($task1->getId())->not->toBe($task2->getId());
});

it('reports as serializable', function () {
    $task = new ClosureTask(fn () => 'result');

    expect($task->isSerializable())->toBeTrue();
});

it('implements TaskContract', function () {
    $task = new ClosureTask(fn () => 'result');

    expect($task)->toBeInstanceOf(LaravelParallel\Contracts\TaskContract::class);
});

it('can be serialized and unserialized', function () {
    $task = new ClosureTask(fn () => 'test value');

    // Access ID before serialization to ensure it's preserved
    $originalId = $task->getId();

    $serialized = serialize($task);
    $unserialized = unserialize($serialized);

    expect($unserialized)->toBeInstanceOf(ClosureTask::class)
        ->and($unserialized->getId())->toBe($originalId);
});

it('lazy loads task ID only when accessed', function () {
    $task = new ClosureTask(fn () => 'test value');

    // Serialize without accessing ID
    $serialized = serialize($task);
    $unserialized = unserialize($serialized);

    // IDs should be generated independently (not shared)
    $id1 = $task->getId();
    $id2 = $unserialized->getId();

    // Both should be valid IDs
    expect($id1)->toBeString()->toStartWith('task_');
    expect($id2)->toBeString()->toStartWith('task_');

    // But they should be different since ID wasn't accessed before serialization
    expect($id1)->not->toBe($id2);
});
