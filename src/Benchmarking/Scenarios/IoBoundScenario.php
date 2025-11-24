<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking\Scenarios;

use LaravelParallel\Contracts\BenchmarkScenario;

/**
 * I/O-bound benchmark scenario.
 *
 * Tests parallel processing performance with I/O-intensive tasks
 * including file operations and simulated network requests.
 */
final readonly class IoBoundScenario implements BenchmarkScenario
{
    public function getCategory(): string
    {
        return 'io-bound';
    }

    public function getDescription(): string
    {
        return 'I/O-intensive tasks: file operations and simulated network requests';
    }

    public function getName(): string
    {
        return 'io-bound';
    }

    public function getTasks(int $iterations): array
    {
        $tasks = [];

        for ($i = 0; $i < $iterations; $i++) {
            $taskType = $i % 2;

            match ($taskType) {
                0 => $tasks["file_{$i}"] = fn () => $this->performFileOperations(),
                1 => $tasks["network_{$i}"] = fn () => $this->simulateNetworkRequest(),
            };
        }

        return $tasks;
    }

    /**
     * Perform file read/write operations.
     *
     * @return int Number of bytes written
     */
    private function performFileOperations(): int
    {
        $tempFile = sys_get_temp_dir().'/benchmark_'.uniqid().'.txt';
        $data = str_repeat('benchmark data ', 1000);

        // Write operation
        $bytesWritten = file_put_contents($tempFile, $data);

        // Read operation
        $readData = file_get_contents($tempFile);

        // Cleanup
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        return $bytesWritten !== false ? $bytesWritten : 0;
    }

    /**
     * Simulate a network request with sleep.
     *
     * @return bool Success status
     */
    private function simulateNetworkRequest(): bool
    {
        // Simulate network latency (10ms)
        usleep(10000);

        // Simulate processing response data
        $data = json_encode(['status' => 'success', 'data' => range(1, 100)]);

        if ($data === false) {
            return false;
        }

        $decoded = json_decode($data, true);

        return is_array($decoded) && isset($decoded['status']) && $decoded['status'] === 'success';
    }
}
