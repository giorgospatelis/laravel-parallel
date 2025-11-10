<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Serialization overhead benchmarks.
 *
 * These benchmarks measure the impact of closure serialization and
 * data payload sizes on parallel execution performance.
 *
 * Key questions answered:
 * - How does payload size affect serialization overhead?
 * - What's the cost of serializing closures with different complexity?
 * - At what payload size does serialization become a bottleneck?
 * - How do captured variables in closures affect performance?
 *
 * README mentions: Per-task overhead ~0.5-1ms includes serialization + IPC.
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class SerializationBench extends BaseBenchmark
{
    /**
     * Benchmark minimal closure (no variables captured).
     *
     * This establishes the baseline serialization cost for the
     * simplest possible closure.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchMinimalClosure(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = fn () => 42;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark closure with captured primitive values.
     *
     * Tests serialization overhead when closures capture simple
     * scalar values (int, string, bool).
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchClosureWithPrimitives(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $value = $i;
            $name = "task-{$i}";
            $enabled = true;

            $tasks[$i] = fn () => $value * 2 . $name . ($enabled ? 'yes' : 'no');
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark closure with small array payload.
     *
     * Tests impact of serializing closures that capture small arrays.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchClosureWithSmallArray(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $data = range(1, 100); // ~100 element array

            $tasks[$i] = fn () => array_sum($data);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark closure with different payload sizes.
     *
     * Tests how payload size impacts serialization overhead.
     * This helps determine recommended payload size limits.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("providePayloadSizes")
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('providePayloadSizes')]
    #[RetryThreshold(5.0)]
    public function benchPayloadSizeImpact(array $params): void
    {
        $size = $params['size'];
        $tasks = [];

        for ($i = 0; $i < 5; $i++) {
            $payload = $this->generatePayload($size);

            $tasks[$i] = fn () => strlen(serialize($payload));
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark closure with nested data structures.
     *
     * Tests serialization of complex nested arrays/objects.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchNestedDataStructures(): void
    {
        $tasks = [];
        for ($i = 0; $i < 5; $i++) {
            $data = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'values' => range(1, 50),
                            'metadata' => [
                                'timestamp' => time(),
                                'id' => $i,
                            ],
                        ],
                    ],
                ],
            ];

            $tasks[$i] = fn () => count($data, COUNT_RECURSIVE);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark: Closure complexity impact.
     *
     * Compares simple vs complex closures to measure if closure
     * body complexity affects serialization.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchSimpleClosureComplexity(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            // Simple closure - one operation
            $tasks[$i] = fn () => $i * 2;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchComplexClosureComplexity(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            // Complex closure - multiple operations
            $tasks[$i] = function () use ($i) {
                $result = 0;
                for ($j = 0; $j < 100; $j++) {
                    $result += $i + $j;
                    $result = $result % 1000;
                }

                return $result;
            };
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark: String manipulation serialization.
     *
     * Tests tasks that capture and manipulate strings.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("provideStringSizes")
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('provideStringSizes')]
    #[RetryThreshold(5.0)]
    public function benchStringSerializationImpact(array $params): void
    {
        $length = $params['length'];
        $tasks = [];

        for ($i = 0; $i < 5; $i++) {
            $text = str_repeat("test-{$i} ", $length / 10);

            $tasks[$i] = fn () => strtoupper($text);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark: Multiple variable capture.
     *
     * Tests overhead of capturing many variables vs few.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchFewVariablesCapture(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $a = $i;

            $tasks[$i] = fn () => $a * 2;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchManyVariablesCapture(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $a = $i;
            $b = $i * 2;
            $c = $i * 3;
            $d = "value-{$i}";
            $e = $i % 2 === 0;
            $f = [$i, $i + 1, $i + 2];

            $tasks[$i] = fn () => $a + $b + $c + strlen($d) + ($e ? 1 : 0) + count($f);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Benchmark: Return value size impact.
     *
     * Tests if returning large values from tasks impacts performance.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("provideReturnSizes")
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('provideReturnSizes')]
    #[RetryThreshold(5.0)]
    public function benchReturnValueSize(array $params): void
    {
        $size = $params['size'];
        $tasks = [];

        for ($i = 0; $i < 5; $i++) {
            $tasks[$i] = fn () => str_repeat('x', $size);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Provide different payload sizes for testing.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function providePayloadSizes(): \Generator
    {
        yield '1KB payload' => ['size' => 1024];
        yield '10KB payload' => ['size' => 10240];
        yield '100KB payload' => ['size' => 102400];
        yield '1MB payload' => ['size' => 1048576];
    }

    /**
     * Provide different string sizes for testing.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideStringSizes(): \Generator
    {
        yield '1KB string' => ['length' => 1024];
        yield '10KB string' => ['length' => 10240];
        yield '100KB string' => ['length' => 102400];
    }

    /**
     * Provide different return value sizes.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideReturnSizes(): \Generator
    {
        yield 'small return (1KB)' => ['size' => 1024];
        yield 'medium return (10KB)' => ['size' => 10240];
        yield 'large return (100KB)' => ['size' => 102400];
    }
}
