<?php

namespace LaravelParallel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \LaravelParallel\ParallelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Parallel' => \LaravelParallel\Facades\Parallel::class,
        ];
    }
}
