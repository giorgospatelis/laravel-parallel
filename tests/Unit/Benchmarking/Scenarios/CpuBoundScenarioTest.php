<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario;

it('has correct name', function () {
    $scenario = new CpuBoundScenario;

    expect($scenario->getName())->toBe('cpu-bound');
});

it('has correct description', function () {
    $scenario = new CpuBoundScenario;

    expect($scenario->getDescription())->toBe('CPU-intensive tasks: prime numbers, Fibonacci, and hashing');
});

it('has correct category', function () {
    $scenario = new CpuBoundScenario;

    expect($scenario->getCategory())->toBe('cpu-bound');
});

it('generates correct number of tasks', function () {
    $scenario = new CpuBoundScenario;

    $tasks = $scenario->getTasks(10);

    expect($tasks)->toBeArray();
    expect($tasks)->toHaveCount(10);
});

it('generates callable tasks', function () {
    $scenario = new CpuBoundScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $task) {
        expect($task)->toBeCallable();
    }
});

it('tasks are executable and return results', function () {
    $scenario = new CpuBoundScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $task) {
        $result = $task();
        expect($result)->toBeInt();
        expect($result)->toBeGreaterThan(0);
    }
});

it('generates mixed task types', function () {
    $scenario = new CpuBoundScenario;

    $tasks = $scenario->getTasks(9);
    $keys = array_keys($tasks);

    // Should have prime, fibonacci, and hash tasks
    expect($keys)->toContain('prime_0');
    expect($keys)->toContain('fibonacci_1');
    expect($keys)->toContain('hash_2');
});

it('handles large iteration counts', function () {
    $scenario = new CpuBoundScenario;

    $tasks = $scenario->getTasks(100);

    expect($tasks)->toHaveCount(100);
});
