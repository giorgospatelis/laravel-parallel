<?php

declare(strict_types=1);

use LaravelParallel\Results\ParallelResult;

describe('successful results', function () {
    it('creates a successful result', function () {
        $result = ParallelResult::success('test value', 1.5);

        expect($result->isSuccess())->toBeTrue()
            ->and($result->isFailure())->toBeFalse()
            ->and($result->getValue())->toBe('test value')
            ->and($result->getExecutionTime())->toBe(1.5)
            ->and($result->getException())->toBeNull();
    });

    it('returns value with getValue', function () {
        $result = ParallelResult::success(42);

        expect($result->getValue())->toBe(42);
    });

    it('returns value with getValueOr', function () {
        $result = ParallelResult::success('actual');

        expect($result->getValueOr('default'))->toBe('actual');
    });

    it('executes callback on ifSuccess', function () {
        $result = ParallelResult::success('value');
        $called = false;

        $result->ifSuccess(function ($value) use (&$called) {
            $called = true;
            expect($value)->toBe('value');
        });

        expect($called)->toBeTrue();
    });

    it('does not execute callback on ifFailure', function () {
        $result = ParallelResult::success('value');
        $called = false;

        $result->ifFailure(function () use (&$called) {
            $called = true;
        });

        expect($called)->toBeFalse();
    });
});

describe('failed results', function () {
    it('creates a failed result', function () {
        $exception = new RuntimeException('Test error');
        $result = ParallelResult::failure($exception, 2.0);

        expect($result->isSuccess())->toBeFalse()
            ->and($result->isFailure())->toBeTrue()
            ->and($result->getException())->toBe($exception)
            ->and($result->getExecutionTime())->toBe(2.0);
    });

    it('throws exception on getValue', function () {
        $exception = new RuntimeException('Test error');
        $result = ParallelResult::failure($exception);

        expect(fn () => $result->getValue())
            ->toThrow(RuntimeException::class, 'Test error');
    });

    it('returns default with getValueOr', function () {
        $exception = new RuntimeException('Test error');
        $result = ParallelResult::failure($exception);

        expect($result->getValueOr('default'))->toBe('default');
    });

    it('executes callback on ifFailure', function () {
        $exception = new RuntimeException('Test error');
        $result = ParallelResult::failure($exception);
        $called = false;

        $result->ifFailure(function ($e) use (&$called, $exception) {
            $called = true;
            expect($e)->toBe($exception);
        });

        expect($called)->toBeTrue();
    });

    it('does not execute callback on ifSuccess', function () {
        $exception = new RuntimeException('Test error');
        $result = ParallelResult::failure($exception);
        $called = false;

        $result->ifSuccess(function () use (&$called) {
            $called = true;
        });

        expect($called)->toBeFalse();
    });
});

it('implements ResultContract', function () {
    $result = ParallelResult::success('value');

    expect($result)->toBeInstanceOf(LaravelParallel\Contracts\ResultContract::class);
});
