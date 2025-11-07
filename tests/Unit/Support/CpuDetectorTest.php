<?php

declare(strict_types=1);

use LaravelParallel\Support\CpuDetector;

beforeEach(function () {
    // Clear cache before each test
    CpuDetector::clearCache();
});

it('detects CPU cores successfully', function () {
    $detector = new CpuDetector();
    $cores = $detector->detect();

    expect($cores)->toBeInt()
        ->toBeGreaterThan(0);
});

it('caches CPU core count after first detection', function () {
    $detector = new CpuDetector();

    $firstCall = $detector->detect();
    $secondCall = $detector->detect();

    expect($firstCall)->toBe($secondCall);
});

it('can clear the cache', function () {
    $detector = new CpuDetector();

    $detector->detect();
    CpuDetector::clearCache();

    // Should not throw even after cache clear
    $cores = $detector->detect();
    expect($cores)->toBeGreaterThan(0);
});

it('returns consistent results across multiple instances', function () {
    $detector1 = new CpuDetector();
    $detector2 = new CpuDetector();

    $cores1 = $detector1->detect();
    $cores2 = $detector2->detect();

    expect($cores1)->toBe($cores2);
});
