<?php

declare(strict_types=1);

namespace LaravelParallel\Console;

use Illuminate\Console\Command;
use LaravelParallel\Facades\Parallel;
use LaravelParallel\Results\ExecutionMetrics;
use LaravelParallel\Results\ResultCollection;

/**
 * Artisan command to test parallel execution.
 *
 * This command demonstrates the package functionality and can be used
 * for performance testing and debugging.
 */
final class ParallelTestCommand extends Command
{
    protected $description = 'Test parallel processing with configurable parameters';

    protected $signature = 'parallel:test
                            {--workers=4 : Number of worker processes}
                            {--tasks=10 : Number of tasks to execute}
                            {--delay=100 : Delay per task in milliseconds}';

    public function handle(): int
    {
        $workers = (int) $this->option('workers');
        $taskCount = (int) $this->option('tasks');
        $delay = (int) $this->option('delay');

        $this->info("Running {$taskCount} parallel tasks with {$workers} workers...");

        $tasks = [];
        for ($i = 1; $i <= $taskCount; $i++) {
            $tasks["task_{$i}"] = function () use ($i, $delay) {
                usleep($delay * 1000); // Convert to microseconds

                return "Result from task {$i}";
            };
        }

        $startTime = microtime(true);

        $results = Parallel::workers($workers)->run($tasks);

        $totalTime = microtime(true) - $startTime;

        $this->displayResults($results, $totalTime);

        return self::SUCCESS;
    }

    /**
     * @param array<int|string, \LaravelParallel\Contracts\ResultContract> $results
     */
    private function displayResults(array $results, float $totalTime): void
    {
        $collection = new ResultCollection($results);
        $metrics = ExecutionMetrics::fromResults($collection);

        $this->newLine();
        $this->info('=== Execution Results ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tasks', $metrics->totalTasks],
                ['Successful', $metrics->successfulTasks],
                ['Failed', $metrics->failedTasks],
                ['Success Rate', number_format($metrics->successRate(), 2).'%'],
                ['Total Time', number_format($totalTime, 3).'s'],
                ['Avg Task Time', number_format($metrics->averageExecutionTime, 3).'s'],
                ['Min Task Time', number_format($metrics->minExecutionTime, 3).'s'],
                ['Max Task Time', number_format($metrics->maxExecutionTime, 3).'s'],
            ]
        );

        if ($collection->anyFailed()) {
            $this->newLine();
            $this->error('Failed tasks:');
            foreach ($collection->failed() as $key => $result) {
                $exception = $result->getException();
                $message = $exception !== null ? $exception->getMessage() : 'Unknown error';
                $this->line("  - {$key}: {$message}");
            }
        }
    }
}
