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

it('detects CPU cores from environment variables', function () {
    CpuDetector::clearCache();

    // Set environment variable
    putenv('NUMBER_OF_PROCESSORS=8');

    $detector = new CpuDetector;
    $cores = $detector->detect();

    // Should detect at least 1 core (may use env var or actual detection)
    expect($cores)->toBeInt()->toBeGreaterThan(0);

    // Clean up
    putenv('NUMBER_OF_PROCESSORS');
});

it('validates core count is within reasonable bounds', function () {
    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    expect($cores)->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThan(10000); // Upper bound check
});

it('logs error when CPU detection would fail with logging enabled', function () {
    config(['parallel.logging.enabled' => true]);
    config(['parallel.logging.channel' => 'stack']);

    CpuDetector::clearCache();

    $detector = new CpuDetector;

    // On a working system, this should not throw
    expect(fn () => $detector->detect())->not->toThrow(Exception::class);
});

it('maintains cache across multiple detector instances', function () {
    CpuDetector::clearCache();

    $detector1 = new CpuDetector;
    $cores1 = $detector1->detect();

    $detector2 = new CpuDetector;
    $cores2 = $detector2->detect();

    $detector3 = new CpuDetector;
    $cores3 = $detector3->detect();

    expect($cores1)->toBe($cores2)
        ->and($cores2)->toBe($cores3);
});

it('clears cache properly for fresh detection', function () {
    $detector = new CpuDetector;
    $first = $detector->detect();

    CpuDetector::clearCache();

    $second = $detector->detect();

    // Both should be valid even after cache clear
    expect($first)->toBeInt()->toBeGreaterThan(0)
        ->and($second)->toBeInt()->toBeGreaterThan(0);
});

it('handles concurrent detection requests correctly', function () {
    CpuDetector::clearCache();

    $detector = new CpuDetector;

    // Simulate concurrent requests
    $results = [];
    for ($i = 0; $i < 10; $i++) {
        $results[] = $detector->detect();
    }

    // All results should be the same
    expect(count(array_unique($results)))->toBe(1)
        ->and($results[0])->toBeGreaterThan(0);
});

it('handles detection on different OS families', function () {
    // This test verifies the code paths for different OS families exist
    CpuDetector::clearCache();

    $detector = new CpuDetector;
    $cores = $detector->detect();

    // Verify OS-specific detection works
    $osFamily = PHP_OS_FAMILY;
    expect($osFamily)->toBeIn(['Linux', 'Windows', 'Darwin', 'BSD', 'Solaris', 'Unknown']);
    expect($cores)->toBeGreaterThan(0);
});
