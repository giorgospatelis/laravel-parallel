<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\Scenarios\MixedWorkloadScenario;

it('has correct name', function () {
    $scenario = new MixedWorkloadScenario;

    expect($scenario->getName())->toBe('mixed');
});

it('has correct description', function () {
    $scenario = new MixedWorkloadScenario;

    expect($scenario->getDescription())->toBe('Mixed workload: CPU + I/O operations simulating real-world tasks');
});

it('has correct category', function () {
    $scenario = new MixedWorkloadScenario;

    expect($scenario->getCategory())->toBe('mixed');
});

it('generates correct number of tasks', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(10);

    expect($tasks)->toBeArray();
    expect($tasks)->toHaveCount(10);
});

it('generates callable tasks', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $task) {
        expect($task)->toBeCallable();
    }
});

it('tasks execute and return results array', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(3);

    foreach ($tasks as $taskId => $task) {
        $result = $task();

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['task_id', 'items_processed', 'total_operations']);
        expect($result['items_processed'])->toBeInt();
        expect($result['items_processed'])->toBeGreaterThan(0);
        expect($result['total_operations'])->toBeGreaterThan(0);
    }
});

it('processes expected number of items', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(1);
    $task = array_values($tasks)[0];

    $result = $task();

    expect($result['items_processed'])->toBe(1000);
});

it('cleans up temporary files', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(2);

    foreach ($tasks as $task) {
        $task(); // Execute task
    }

    // Check that temp directory doesn't accumulate files
    $tempDir = sys_get_temp_dir();
    $benchmarkFiles = glob($tempDir.'/mixed_benchmark_*.json');

    expect($benchmarkFiles)->toBeArray();
    // Should have very few or no leftover files
    expect(count($benchmarkFiles))->toBeLessThan(5);
});

it('performs both CPU and IO operations', function () {
    $scenario = new MixedWorkloadScenario;

    $tasks = $scenario->getTasks(1);
    $task = array_values($tasks)[0];

    $start = microtime(true);
    $result = $task();
    $elapsed = microtime(true) - $start;

    // Should take some time due to I/O operations (usleep)
    expect($elapsed)->toBeGreaterThan(0);

    // Should have processed data (CPU operations)
    expect($result['total_operations'])->toBeGreaterThan(1000);
});
