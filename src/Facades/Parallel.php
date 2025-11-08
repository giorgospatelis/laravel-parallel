<?php

declare(strict_types=1);

namespace LaravelParallel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for parallel processing.
 *
 * @method static \LaravelParallel\Core\ParallelManager workers(int $count)
 * @method static \LaravelParallel\Core\ParallelManager timeout(float $seconds)
 * @method static array<int|string, \LaravelParallel\Contracts\ResultContract> run(array<int|string, callable> $closures)
 * @method static array<int|string, \LaravelParallel\Contracts\ResultContract> map(iterable<int|string, mixed> $items, callable $callback)
 *
 * @see \LaravelParallel\Core\ParallelManager
 */
final class Parallel extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'parallel';
    }
}
