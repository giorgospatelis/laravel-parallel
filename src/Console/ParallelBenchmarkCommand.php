<?php

declare(strict_types=1);

namespace LaravelParallel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use LaravelParallel\Benchmarking\BenchmarkResult;
use LaravelParallel\Benchmarking\BenchmarkRunner;
use LaravelParallel\Contracts\BenchmarkScenario;

/**
 * Artisan command for running performance benchmarks.
 *
 * Executes benchmark scenarios to measure parallel processing performance
 * and provides results in various output formats.
 */
final class ParallelBenchmarkCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run performance benchmarks for parallel task execution';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parallel:benchmark
                            {scenario? : Specific scenario to run (or "all" for all scenarios)}
                            {--workers= : Number of workers to use (default: auto-detect)}
                            {--iterations=100 : Number of iterations per scenario}
                            {--export= : Export format: json, csv, or markdown}';

    /**
     * Execute the console command.
     */
    public function handle(BenchmarkRunner $runner): int
    {
        if (! Config::get('parallel.benchmarking.enabled', true)) {
            $this->error('Benchmarking is disabled in configuration.');

            return self::FAILURE;
        }

        $scenarioName = $this->argument('scenario');
        $workerCount = $this->option('workers') !== null ? (int) $this->option('workers') : null;
        $iterations = (int) $this->option('iterations');
        $exportFormat = $this->option('export');

        // Validate inputs
        if ($iterations < 1) {
            $this->error('Iterations must be at least 1.');

            return self::FAILURE;
        }

        if ($workerCount !== null && $workerCount < 1) {
            $this->error('Worker count must be at least 1.');

            return self::FAILURE;
        }

        // Get available scenarios
        $scenarios = $this->getAvailableScenarios();

        if (empty($scenarios)) {
            $this->error('No benchmark scenarios are configured.');

            return self::FAILURE;
        }

        // If no scenario specified, list available scenarios
        if ($scenarioName === null) {
            $this->listScenarios($scenarios);

            return self::SUCCESS;
        }

        // Determine which scenarios to run
        $scenariosToRun = [];

        if ($scenarioName === 'all') {
            $scenariosToRun = $scenarios;
        } elseif (isset($scenarios[$scenarioName])) {
            $scenariosToRun = [$scenarioName => $scenarios[$scenarioName]];
        } else {
            $this->error("Unknown scenario: {$scenarioName}");
            $this->line('Available scenarios: '.implode(', ', array_keys($scenarios)));

            return self::FAILURE;
        }

        // Run benchmarks
        $this->info('Running benchmarks...');
        $this->line('Workers: '.($workerCount ?? 'auto-detect'));
        $this->line("Iterations: {$iterations}");
        $this->newLine();

        $results = [];
        foreach ($scenariosToRun as $name => $scenarioClass) {
            $scenario = new $scenarioClass;
            $this->info("Running: {$scenario->getDescription()}");

            $result = $runner->run($scenario, $workerCount, $iterations);
            $results[] = $result;

            $this->line("  ✓ Completed in {$result->getFormattedTotalTime()}");
        }

        $this->newLine();

        // Display results
        if ($exportFormat !== null) {
            $this->exportResults($results, $exportFormat);
        } else {
            $this->displayResults($results);
        }

        return self::SUCCESS;
    }

    /**
     * Display benchmark results in console table format.
     *
     * @param  array<BenchmarkResult>  $results
     */
    private function displayResults(array $results): void
    {
        $this->info('Benchmark Results:');
        $this->newLine();

        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                $result->scenarioName,
                $result->category,
                $result->iterations,
                $result->workerCount,
                $result->getFormattedTotalTime(),
                $result->getFormattedAverageTime(),
                $result->getFormattedThroughput(),
                $result->getFormattedMemory(),
            ];
        }

        $this->table(
            ['Scenario', 'Category', 'Iterations', 'Workers', 'Total Time', 'Avg/Task', 'Throughput', 'Memory'],
            $rows
        );
    }

    /**
     * Export results as CSV.
     *
     * @param  array<BenchmarkResult>  $results
     */
    private function exportCsv(array $results): string
    {
        $csv = "Scenario,Category,Iterations,Workers,Total Time (s),Avg Time (ms),Throughput (tasks/s),Memory (bytes)\n";

        foreach ($results as $result) {
            $csv .= implode(',', [
                $result->scenarioName,
                $result->category,
                $result->iterations,
                $result->workerCount,
                number_format($result->totalTime, 4),
                number_format($result->averageTimePerTask * 1000, 2),
                number_format($result->throughput, 2),
                $result->memoryPeakBytes,
            ])."\n";
        }

        return $csv;
    }

    /**
     * Export results as JSON.
     *
     * @param  array<BenchmarkResult>  $results
     */
    private function exportJson(array $results): string
    {
        $data = array_map(fn (BenchmarkResult $r) => $r->toArray(), $results);

        return json_encode([
            'timestamp' => date('c'),
            'results' => $data,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Export results as Markdown.
     *
     * @param  array<BenchmarkResult>  $results
     */
    private function exportMarkdown(array $results): string
    {
        $md = "# Laravel Parallel Benchmark Results\n\n";
        $md .= '**Date:** '.date('Y-m-d H:i:s')."\n\n";
        $md .= "## Results\n\n";
        $md .= "| Scenario | Category | Iterations | Workers | Total Time | Avg/Task | Throughput | Memory |\n";
        $md .= "|----------|----------|------------|---------|------------|----------|------------|--------|\n";

        foreach ($results as $result) {
            $md .= sprintf(
                "| %s | %s | %d | %d | %s | %s | %s | %s |\n",
                $result->scenarioName,
                $result->category,
                $result->iterations,
                $result->workerCount,
                $result->getFormattedTotalTime(),
                $result->getFormattedAverageTime(),
                $result->getFormattedThroughput(),
                $result->getFormattedMemory()
            );
        }

        return $md;
    }

    /**
     * Export benchmark results to specified format.
     *
     * @param  array<BenchmarkResult>  $results
     */
    private function exportResults(array $results, string $format): void
    {
        $content = match (mb_strtolower($format)) {
            'json' => $this->exportJson($results),
            'csv' => $this->exportCsv($results),
            'markdown' => $this->exportMarkdown($results),
            default => null,
        };

        if ($content === null) {
            $this->error("Unknown export format: {$format}");
            $this->line('Supported formats: json, csv, markdown');

            return;
        }

        $filename = 'benchmark_'.date('Y-m-d_His').'.'.$format;
        file_put_contents($filename, $content);

        $this->info("Results exported to: {$filename}");
    }

    /**
     * Get available benchmark scenarios from configuration.
     *
     * @return array<string, class-string<BenchmarkScenario>>
     */
    private function getAvailableScenarios(): array
    {
        return Config::get('parallel.benchmarking.scenarios', []);
    }

    /**
     * List available benchmark scenarios.
     *
     * @param  array<string, class-string<BenchmarkScenario>>  $scenarios
     */
    private function listScenarios(array $scenarios): void
    {
        $this->info('Available Benchmark Scenarios:');
        $this->newLine();

        $rows = [];
        foreach ($scenarios as $name => $scenarioClass) {
            $scenario = new $scenarioClass;
            $rows[] = [
                $name,
                $scenario->getCategory(),
                $scenario->getDescription(),
            ];
        }

        $this->table(['Name', 'Category', 'Description'], $rows);

        $this->newLine();
        $this->line('Run a specific scenario:');
        $this->line('  php artisan parallel:benchmark <scenario>');
        $this->newLine();
        $this->line('Run all scenarios:');
        $this->line('  php artisan parallel:benchmark all');
    }
}
