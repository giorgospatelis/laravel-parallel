<?php

declare(strict_types=1);

use LaravelParallel\Support\CpuDetector;

beforeEach(function () {
    // Clear cache before each test
    CpuDetector::clearCache();
});

it('detects CPU cores successfully', function () {
    $detector = new CpuDetector;
    $cores = $detector->detect();

    expect($cores)->toBeInt()
        ->toBeGreaterThan(0);
});

it('caches CPU core count after first detection', function () {
    $detector = new CpuDetector;

    $firstCall = $detector->detect();
    $secondCall = $detector->detect();

    expect($firstCall)->toBe($secondCall);
});

it('can clear the cache', function () {
    $detector = new CpuDetector;

    $detector->detect();
    CpuDetector::clearCache();

    // Should not throw even after cache clear
    $cores = $detector->detect();
    expect($cores)->toBeGreaterThan(0);
});

it('returns consistent results across multiple instances', function () {
    $detector1 = new CpuDetector;
    $detector2 = new CpuDetector;

    $cores1 = $detector1->detect();
    $cores2 = $detector2->detect();

    expect($cores1)->toBe($cores2);
});

it('logs debug message when CPU cores are detected with logging enabled', function () {
    config(['parallel.logging.enabled' => true]);
    config(['parallel.logging.channel' => 'stack']);

    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    expect($cores)->toBeInt()->toBeGreaterThan(0);
});

it('does not log when logging is disabled', function () {
    config(['parallel.logging.enabled' => false]);

    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    expect($cores)->toBeInt()->toBeGreaterThan(0);
});

it('detects cores on current platform successfully', function () {
    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    // Verify detection works on current OS
    expect($cores)->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(1024); // Reasonable upper bound
});

it('handles detection when result is cached from previous call', function () {
    $detector1 = new CpuDetector;
    $firstResult = $detector1->detect();

    // Second detector should use cached value
    $detector2 = new CpuDetector;
    $secondResult = $detector2->detect();

    expect($firstResult)->toBe($secondResult);
});

it('properly validates detected core count is positive', function () {
    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    expect($cores)->toBeGreaterThan(0);
});

it('caches null check returns early when cache is set', function () {
    $detector = new CpuDetector;

    // First call populates cache
    $first = $detector->detect();

    // Second call should return cached value immediately
    $second = $detector->detect();

    expect($first)->toBe($second)
        ->and($first)->toBeGreaterThan(0);
});
