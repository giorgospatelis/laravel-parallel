<?php

declare(strict_types=1);

namespace LaravelParallel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for parallel processing.
 *
 * @method static \LaravelParallel\Core\ParallelManager workers(int $count)
 * @method static \LaravelParallel\Core\ParallelManager timeout(float $seconds)
 * @method static array<int|string, \LaravelParallel\Results\ParallelResult> run(array $closures)
 * @method static array<int|string, \LaravelParallel\Results\ParallelResult> map(iterable $items, callable $callback)
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
