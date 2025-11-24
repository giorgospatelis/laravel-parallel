<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking\Scenarios;

use LaravelParallel\Contracts\BenchmarkScenario;

/**
 * Mixed workload benchmark scenario.
 *
 * Tests parallel processing performance with realistic mixed workloads
 * combining CPU-intensive and I/O-intensive operations.
 */
final readonly class MixedWorkloadScenario implements BenchmarkScenario
{
    public function getCategory(): string
    {
        return 'mixed';
    }

    public function getDescription(): string
    {
        return 'Mixed workload: CPU + I/O operations simulating real-world tasks';
    }

    public function getName(): string
    {
        return 'mixed';
    }

    public function getTasks(int $iterations): array
    {
        $tasks = [];

        for ($i = 0; $i < $iterations; $i++) {
            $tasks["mixed_{$i}"] = fn () => $this->processDataWithIo($i);
        }

        return $tasks;
    }

    /**
     * Check if a number is prime.
     *
     * @param  int  $num  Number to check
     * @return bool True if prime
     */
    private function isPrime(int $num): bool
    {
        if ($num <= 1) {
            return false;
        }

        if ($num <= 3) {
            return true;
        }

        if ($num % 2 === 0 || $num % 3 === 0) {
            return false;
        }

        for ($i = 5; $i * $i <= $num; $i += 6) {
            if ($num % $i === 0 || $num % ($i + 2) === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process data combining CPU and I/O operations.
     *
     * Simulates a realistic scenario: fetch data (I/O), process it (CPU),
     * and save results (I/O).
     *
     * @param  int  $taskId  Task identifier
     * @return array<string, mixed> Processing results
     */
    private function processDataWithIo(int $taskId): array
    {
        // Simulate fetching data (I/O)
        usleep(5000); // 5ms network latency
        $rawData = range(1, 1000);

        // CPU-intensive processing
        $processedData = [];
        foreach ($rawData as $value) {
            // Simulate data transformation
            $processedData[] = [
                'id' => $value,
                'squared' => $value * $value,
                'hash' => hash('md5', (string) $value),
                'isPrime' => $this->isPrime($value),
            ];
        }

        // Simulate saving results (I/O)
        $tempFile = sys_get_temp_dir().'/mixed_benchmark_'.$taskId.'.json';
        file_put_contents($tempFile, json_encode($processedData));

        // Cleanup
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        return [
            'task_id' => $taskId,
            'items_processed' => count($processedData),
            'total_operations' => count($processedData) * 3, // squared + hash + isPrime
        ];
    }
}
