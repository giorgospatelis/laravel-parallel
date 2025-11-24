<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking\Scenarios;

use LaravelParallel\Contracts\BenchmarkScenario;

/**
 * CPU-bound benchmark scenario.
 *
 * Tests parallel processing performance with CPU-intensive tasks
 * including prime number calculations, Fibonacci sequences, and hashing.
 */
final readonly class CpuBoundScenario implements BenchmarkScenario
{
    public function getCategory(): string
    {
        return 'cpu-bound';
    }

    public function getDescription(): string
    {
        return 'CPU-intensive tasks: prime numbers, Fibonacci, and hashing';
    }

    public function getName(): string
    {
        return 'cpu-bound';
    }

    public function getTasks(int $iterations): array
    {
        $tasks = [];

        for ($i = 0; $i < $iterations; $i++) {
            $taskType = $i % 3;

            match ($taskType) {
                0 => $tasks["prime_{$i}"] = fn () => $this->calculatePrimes(10000),
                1 => $tasks["fibonacci_{$i}"] = fn () => $this->calculateFibonacci(30),
                2 => $tasks["hash_{$i}"] = fn () => $this->calculateHashes(1000),
            };
        }

        return $tasks;
    }

    /**
     * Calculate Fibonacci number at the given position.
     *
     * @param  int  $n  Position in Fibonacci sequence
     * @return int Fibonacci number
     */
    private function calculateFibonacci(int $n): int
    {
        if ($n <= 1) {
            return $n;
        }

        $prev = 0;
        $curr = 1;

        for ($i = 2; $i <= $n; $i++) {
            $temp = $curr;
            $curr = $prev + $curr;
            $prev = $temp;
        }

        return $curr;
    }

    /**
     * Calculate multiple hashes.
     *
     * @param  int  $count  Number of hashes to calculate
     * @return int Count of hashes calculated
     */
    private function calculateHashes(int $count): int
    {
        $data = 'benchmark_data_string';
        $hashes = [];

        for ($i = 0; $i < $count; $i++) {
            $hashes[] = hash('sha256', $data.$i);
        }

        return count($hashes);
    }

    /**
     * Calculate prime numbers up to the given limit.
     *
     * @param  int  $limit  Upper limit for prime calculation
     * @return int Count of primes found
     */
    private function calculatePrimes(int $limit): int
    {
        $primes = [];

        for ($num = 2; $num <= $limit; $num++) {
            $isPrime = true;

            for ($i = 2; $i <= sqrt($num); $i++) {
                if ($num % $i === 0) {
                    $isPrime = false;
                    break;
                }
            }

            if ($isPrime) {
                $primes[] = $num;
            }
        }

        return count($primes);
    }
}
