<?php

declare(strict_types=1);

use LaravelParallel\Facades\Parallel;

it('can access facade', function () {
    expect(Parallel::getFacadeRoot())->not->toBeNull();
});

it('facade resolves to ParallelManager', function () {
    $manager = Parallel::getFacadeRoot();

    expect($manager)->toBeInstanceOf(LaravelParallel\Core\ParallelManager::class);
});

it('supports method chaining through facade', function () {
    $manager = Parallel::workers(2)->timeout(30.0);

    expect($manager)->toBeInstanceOf(LaravelParallel\Core\ParallelManager::class);
});

it('returns ParallelManager from workers method', function () {
    $manager = Parallel::workers(4);

    expect($manager)->toBeInstanceOf(LaravelParallel\Core\ParallelManager::class)
        ->and($manager->getWorkerCount())->toBe(4);
});

it('returns ParallelManager from timeout method', function () {
    $manager = app('parallel')->timeout(10.0);

    expect($manager)->toBeInstanceOf(LaravelParallel\Core\ParallelManager::class);
});

it('can chain multiple configuration methods', function () {
    $manager = Parallel::workers(4)->timeout(15.0);

    expect($manager)->toBeInstanceOf(LaravelParallel\Core\ParallelManager::class)
        ->and($manager->getWorkerCount())->toBe(4);
});

it('resolves fresh instance each time from container', function () {
    $manager1 = app('parallel');
    $manager2 = app('parallel');

    // Should be different instances for Octane safety
    expect($manager1)->not->toBe($manager2);
});
