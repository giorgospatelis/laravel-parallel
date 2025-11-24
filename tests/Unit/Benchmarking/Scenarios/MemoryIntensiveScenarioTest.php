<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\Scenarios\MemoryIntensiveScenario;

it('has correct name', function () {
    $scenario = new MemoryIntensiveScenario;

    expect($scenario->getName())->toBe('memory');
});

it('has correct description', function () {
    $scenario = new MemoryIntensiveScenario;

    expect($scenario->getDescription())->toBe('Memory-intensive tasks: large array and string operations');
});

it('has correct category', function () {
    $scenario = new MemoryIntensiveScenario;

    expect($scenario->getCategory())->toBe('memory');
});

it('generates correct number of tasks', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(10);

    expect($tasks)->toBeArray();
    expect($tasks)->toHaveCount(10);
});

it('generates callable tasks', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $task) {
        expect($task)->toBeCallable();
    }
});

it('array tasks execute and return count', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(2);
    $arrayTask = $tasks['array_0'];

    $result = $arrayTask();

    expect($result)->toBeInt();
    expect($result)->toBeGreaterThan(0);
});

it('string tasks execute and return word count', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(2);
    $stringTask = $tasks['string_1'];

    $result = $stringTask();

    expect($result)->toBeInt();
    expect($result)->toBeGreaterThan(0);
});

it('generates mixed task types', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(4);
    $keys = array_keys($tasks);

    // Should have array and string tasks
    expect($keys)->toContain('array_0');
    expect($keys)->toContain('string_1');
});

it('array tasks process large datasets', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(1);
    $arrayTask = $tasks['array_0'];

    $memoryBefore = memory_get_usage();
    $result = $arrayTask();
    $memoryAfter = memory_get_usage();

    $memoryUsed = $memoryAfter - $memoryBefore;

    // Should use significant memory for large array
    expect($result)->toBeGreaterThan(1000);
});

it('string tasks process large texts', function () {
    $scenario = new MemoryIntensiveScenario;

    $tasks = $scenario->getTasks(2);
    $stringTask = $tasks['string_1'];

    $memoryBefore = memory_get_usage();
    $result = $stringTask();
    $memoryAfter = memory_get_usage();

    // Should return word count from large text
    expect($result)->toBeGreaterThan(1000);
});
