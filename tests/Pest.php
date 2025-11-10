<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(LaravelParallel\Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

// Core tests need Laravel's test case for config and mocking
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Core');

// Worker tests need Laravel's test case for mocking
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Workers');

// Support tests need Laravel's test case for config
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Support');

// Tasks tests need Laravel's test case for serialization support
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Tasks');

// Config tests need Laravel's test case for config helper
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Config');

// Events tests need Laravel's test case for event dispatching
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Events');

// Console tests need Laravel's test case for artisan testing
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Unit/Console');

// Integration tests need Laravel's test case for full stack testing
pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Integration/Horizon');

pest()->extend(LaravelParallel\Tests\TestCase::class)
    ->in('Integration/Telescope');

// Other Unit tests (Exceptions, Results) use default PHPUnit TestCase
pest()->in('Unit/Exceptions');
pest()->in('Unit/Results');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
