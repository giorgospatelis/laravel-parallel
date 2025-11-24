<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking\Scenarios;

use LaravelParallel\Contracts\BenchmarkScenario;

/**
 * Memory-intensive benchmark scenario.
 *
 * Tests parallel processing performance with memory-intensive operations
 * including large array manipulations and string processing.
 */
final readonly class MemoryIntensiveScenario implements BenchmarkScenario
{
    public function getCategory(): string
    {
        return 'memory';
    }

    public function getDescription(): string
    {
        return 'Memory-intensive tasks: large array and string operations';
    }

    public function getName(): string
    {
        return 'memory';
    }

    public function getTasks(int $iterations): array
    {
        $tasks = [];

        for ($i = 0; $i < $iterations; $i++) {
            $taskType = $i % 2;

            match ($taskType) {
                0 => $tasks["array_{$i}"] = fn () => $this->processLargeArray(),
                1 => $tasks["string_{$i}"] = fn () => $this->processLargeString(),
            };
        }

        return $tasks;
    }

    /**
     * Process large array operations.
     *
     * @return int Number of elements processed
     */
    private function processLargeArray(): int
    {
        // Create large array
        $data = range(1, 50000);

        // Multiple transformations
        $mapped = array_map(fn ($x) => $x * 2, $data);
        $filtered = array_filter($mapped, fn ($x) => $x % 4 === 0);
        $reduced = array_reduce($filtered, fn ($carry, $item) => $carry + $item, 0);

        // Additional processing
        $sorted = $filtered;
        rsort($sorted);

        return count($filtered);
    }

    /**
     * Process large string operations.
     *
     * @return int Number of operations performed
     */
    private function processLargeString(): int
    {
        // Create large string
        $text = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 5000);

        // String operations
        $upper = mb_strtoupper($text);
        $lower = mb_strtolower($text);
        $words = str_word_count($text);

        // Pattern matching
        $replaced = str_replace('Lorem', 'Benchmark', $text);

        // Hashing large string
        $hash = hash('sha256', $text);

        // String splitting and joining
        $parts = explode(' ', mb_substr($text, 0, 10000));
        $joined = implode('-', $parts);

        return $words;
    }
}
