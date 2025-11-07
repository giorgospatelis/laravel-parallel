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
