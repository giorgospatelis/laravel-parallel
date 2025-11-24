<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\Scenarios\IoBoundScenario;

it('has correct name', function () {
    $scenario = new IoBoundScenario;

    expect($scenario->getName())->toBe('io-bound');
});

it('has correct description', function () {
    $scenario = new IoBoundScenario;

    expect($scenario->getDescription())->toBe('I/O-intensive tasks: file operations and simulated network requests');
});

it('has correct category', function () {
    $scenario = new IoBoundScenario;

    expect($scenario->getCategory())->toBe('io-bound');
});

it('generates correct number of tasks', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(10);

    expect($tasks)->toBeArray();
    expect($tasks)->toHaveCount(10);
});

it('generates callable tasks', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $task) {
        expect($task)->toBeCallable();
    }
});

it('file tasks execute and return byte count', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(2);
    $fileTask = $tasks['file_0'];

    $result = $fileTask();

    expect($result)->toBeInt();
    expect($result)->toBeGreaterThan(0);
});

it('network tasks execute and return boolean', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(2);
    $networkTask = $tasks['network_1'];

    $result = $networkTask();

    expect($result)->toBeBool();
    expect($result)->toBeTrue();
});

it('generates mixed task types', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(4);
    $keys = array_keys($tasks);

    // Should have file and network tasks
    expect($keys)->toContain('file_0');
    expect($keys)->toContain('network_1');
});

it('cleans up temporary files', function () {
    $scenario = new IoBoundScenario;

    $tasks = $scenario->getTasks(2);
    $fileTask = $tasks['file_0'];

    $fileTask(); // Execute task

    // Check that temp directory doesn't accumulate files
    $tempDir = sys_get_temp_dir();
    $benchmarkFiles = glob($tempDir.'/benchmark_*.txt');

    expect($benchmarkFiles)->toBeArray();
    // Should have very few or no leftover files
    expect(count($benchmarkFiles))->toBeLessThan(5);
});
