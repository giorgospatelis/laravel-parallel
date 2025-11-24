<?php

declare(strict_types=1);

namespace LaravelParallel\Contracts;

/**
 * Contract for benchmark scenarios.
 *
 * Defines the interface for benchmark scenarios that can be executed
 * to measure parallel processing performance.
 */
interface BenchmarkScenario
{
    /**
     * Get the scenario category.
     *
     * @return string Category: 'cpu-bound', 'io-bound', 'mixed', or 'memory'
     */
    public function getCategory(): string;

    /**
     * Get human-readable scenario description.
     *
     * @return string Description of what this scenario benchmarks
     */
    public function getDescription(): string;

    /**
     * Get the scenario name identifier.
     *
     * @return string Unique scenario identifier (e.g., 'cpu-bound', 'io-bound')
     */
    public function getName(): string;

    /**
     * Generate benchmark tasks for the given number of iterations.
     *
     * @param  int  $iterations  Number of tasks to generate
     * @return array<string, callable> Associative array of task key => callable
     */
    public function getTasks(int $iterations): array;
}
