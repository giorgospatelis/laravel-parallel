<?php

declare(strict_types=1);

use LaravelParallel\Core\ResultCollector;

it('can be instantiated', function () {
    $collector = new ResultCollector;

    expect($collector)->toBeInstanceOf(ResultCollector::class);
});

it('returns empty array when given empty executions', function () {
    $collector = new ResultCollector;

    $results = $collector->collect([]);

    expect($results)->toBeArray()->toBeEmpty();
});

it('collects results with events enabled', function () {
    config(['parallel.events.enabled' => true]);

    $collector = new ResultCollector;

    $results = $collector->collect([]);

    expect($results)->toBeArray();
});

it('collects results with events disabled', function () {
    config(['parallel.events.enabled' => false]);

    $collector = new ResultCollector;

    $results = $collector->collect([]);

    expect($results)->toBeArray();
});

it('handles null timeout parameter', function () {
    $collector = new ResultCollector;

    $results = $collector->collect([], null);

    expect($results)->toBeArray();
});

it('handles positive timeout parameter', function () {
    $collector = new ResultCollector;

    $results = $collector->collect([], 30.0);

    expect($results)->toBeArray();
});

it('handles zero timeout parameter', function () {
    $collector = new ResultCollector;

    $results = $collector->collect([], 0.0);

    expect($results)->toBeArray();
});
