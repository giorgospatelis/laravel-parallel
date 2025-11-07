<?php

declare(strict_types=1);

namespace LaravelParallel\Core;

use Amp\CancelledException;
use Amp\Parallel\Worker\Execution;
use Amp\TimeoutCancellation;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Exceptions\TimeoutException;
use LaravelParallel\Results\ParallelResult;
use Throwable;

/**
 * Collects and aggregates results from parallel executions.
 *
 * This class handles the collection of results from amphp execution handles,
 * tracking execution time and capturing both successful results and exceptions.
 */
final class ResultCollector
{
    /**
     * Collect results from parallel executions.
     *
     * This method waits for all executions to complete, collecting both successful
     * results and exceptions into ParallelResult objects. Execution time is tracked
     * for each task. Events are dispatched for task lifecycle if enabled.
     *
     * If a timeout is specified, tasks that exceed the timeout will throw a TimeoutException.
     *
     * @param  array<string|int, Execution<mixed, mixed, mixed>>  $executions  Execution handles
     * @param  float|null  $timeout  Optional timeout in seconds for each task
     * @return array<string|int, ParallelResult> Results indexed by original keys
     *
     * @throws TimeoutException If a task exceeds the specified timeout
     */
    public function collect(array $executions, ?float $timeout = null): array
    {
        $results = [];
        $eventsEnabled = config('parallel.events.enabled', true);

        foreach ($executions as $key => $execution) {
            if ($eventsEnabled) {
                TaskStarted::dispatch($key, microtime(true));
            }

            $future = $execution->getFuture();
            $startTime = microtime(true);

            try {
                // If timeout is configured, use TimeoutCancellation to enforce it
                if ($timeout !== null && $timeout > 0) {
                    $cancellation = new TimeoutCancellation($timeout);
                    $value = $future->await($cancellation);
                } else {
                    $value = $future->await();
                }

                $executionTime = microtime(true) - $startTime;

                if ($eventsEnabled) {
                    TaskCompleted::dispatch($key, $value, $executionTime);
                }

                $results[$key] = ParallelResult::success($value, $executionTime);
            } catch (CancelledException $error) {
                // TimeoutCancellation throws CancelledException on timeout
                $executionTime = microtime(true) - $startTime;

                $timeoutException = new TimeoutException(
                    sprintf('Task exceeded timeout of %.2f seconds', $timeout ?? 0),
                    previous: $error
                );

                if ($eventsEnabled) {
                    TaskFailed::dispatch($key, $timeoutException, $executionTime);
                }

                $results[$key] = ParallelResult::failure($timeoutException, $executionTime);
            } catch (Throwable $error) {
                $executionTime = microtime(true) - $startTime;

                if ($eventsEnabled) {
                    TaskFailed::dispatch($key, $error, $executionTime);
                }

                $results[$key] = ParallelResult::failure($error, $executionTime);
            }
        }

        return $results;
    }
}
