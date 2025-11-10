<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Real-world scenario benchmarks.
 *
 * These benchmarks simulate realistic use cases for Laravel Parallel:
 * - Image processing (CPU-bound)
 * - Data transformation (array operations)
 * - Hash/encryption (CPU-bound)
 * - JSON processing (mixed CPU/IO)
 * - CSV parsing (mixed CPU/IO)
 * - API simulation (I/O-bound)
 *
 * These scenarios demonstrate practical applications and help users
 * understand when to use parallel processing in their applications.
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class RealWorldBench extends BaseBenchmark
{
    /**
     * Scenario: Image processing simulation.
     *
     * Simulates CPU-intensive image operations like resizing, filtering,
     * or thumbnail generation - common Laravel use case.
     *
     * Sequential vs Parallel comparison.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchImageProcessingSequential(): void
    {
        $imagePaths = [];
        for ($i = 0; $i < 20; $i++) {
            $imagePaths[] = "image-{$i}.jpg";
        }

        $results = array_map(function ($path) {
            // Simulate image processing operations
            // (resize, filter, compress, generate thumbnail)
            return $this->simulateImageProcessing($path);
        }, $imagePaths);
    }

    /**
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchImageProcessingParallel(): void
    {
        $imagePaths = [];
        for ($i = 0; $i < 20; $i++) {
            $imagePaths[] = "image-{$i}.jpg";
        }

        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($imagePaths, function ($path) {
                return $this->simulateImageProcessing($path);
            });
    }

    /**
     * Scenario: Data transformation (ETL operations).
     *
     * Simulates transforming large datasets - common in data processing,
     * reporting, and analytics applications.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchDataTransformationSequential(): void
    {
        $chunks = [];
        for ($i = 0; $i < 20; $i++) {
            $chunks[] = range($i * 1000, ($i + 1) * 1000 - 1);
        }

        $results = array_map(function ($chunk) {
            return $this->transformDataChunk($chunk);
        }, $chunks);
    }

    /**
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchDataTransformationParallel(): void
    {
        $chunks = [];
        for ($i = 0; $i < 20; $i++) {
            $chunks[] = range($i * 1000, ($i + 1) * 1000 - 1);
        }

        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($chunks, function ($chunk) {
                return $this->transformDataChunk($chunk);
            });
    }

    /**
     * Scenario: Password hashing (security operations).
     *
     * Simulates bulk password hashing or token generation - CPU-intensive
     * cryptographic operations common in user management.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchPasswordHashingSequential(): void
    {
        $passwords = [];
        for ($i = 0; $i < 20; $i++) {
            $passwords[] = "password-{$i}";
        }

        $results = array_map(function ($password) {
            // Simulate bcrypt hashing (CPU-intensive)
            return $this->simulatePasswordHash($password);
        }, $passwords);
    }

    /**
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchPasswordHashingParallel(): void
    {
        $passwords = [];
        for ($i = 0; $i < 20; $i++) {
            $passwords[] = "password-{$i}";
        }

        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($passwords, function ($password) {
                return $this->simulatePasswordHash($password);
            });
    }

    /**
     * Scenario: JSON processing (API data transformation).
     *
     * Simulates processing JSON responses from external APIs - common in
     * data aggregation and microservice communication.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchJsonProcessingSequential(): void
    {
        $jsonData = [];
        for ($i = 0; $i < 30; $i++) {
            $jsonData[] = $this->generateLargeJsonPayload();
        }

        $results = array_map(function ($json) {
            return $this->processJsonData($json);
        }, $jsonData);
    }

    /**
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchJsonProcessingParallel(): void
    {
        $jsonData = [];
        for ($i = 0; $i < 30; $i++) {
            $jsonData[] = $this->generateLargeJsonPayload();
        }

        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($jsonData, function ($json) {
                return $this->processJsonData($json);
            });
    }

    /**
     * Scenario: Report generation.
     *
     * Simulates generating multiple reports concurrently - common in
     * dashboard and analytics applications.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchReportGenerationSequential(): void
    {
        $reportConfigs = [
            ['type' => 'sales', 'range' => 'monthly'],
            ['type' => 'users', 'range' => 'weekly'],
            ['type' => 'revenue', 'range' => 'quarterly'],
            ['type' => 'inventory', 'range' => 'daily'],
            ['type' => 'analytics', 'range' => 'monthly'],
        ];

        $results = array_map(function ($config) {
            return $this->generateReport($config);
        }, $reportConfigs);
    }

    /**
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchReportGenerationParallel(): void
    {
        $reportConfigs = [
            ['type' => 'sales', 'range' => 'monthly'],
            ['type' => 'users', 'range' => 'weekly'],
            ['type' => 'revenue', 'range' => 'quarterly'],
            ['type' => 'inventory', 'range' => 'daily'],
            ['type' => 'analytics', 'range' => 'monthly'],
        ];

        \LaravelParallel\Facades\Parallel::run(array_map(
            fn ($config) => fn () => $this->generateReport($config),
            $reportConfigs
        ));
    }

    /**
     * Scenario: Bulk file validation.
     *
     * Simulates validating uploaded files (checksums, formats, content scanning).
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchFileValidationSequential(): void
    {
        $files = [];
        for ($i = 0; $i < 25; $i++) {
            $files[] = [
                'name' => "file-{$i}.pdf",
                'size' => rand(1024, 1024000),
                'content' => str_repeat('x', rand(1000, 5000)),
            ];
        }

        $results = array_map(function ($file) {
            return $this->validateFile($file);
        }, $files);
    }

    /**
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchFileValidationParallel(): void
    {
        $files = [];
        for ($i = 0; $i < 25; $i++) {
            $files[] = [
                'name' => "file-{$i}.pdf",
                'size' => rand(1024, 1024000),
                'content' => str_repeat('x', rand(1000, 5000)),
            ];
        }

        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($files, function ($file) {
                return $this->validateFile($file);
            });
    }

    // =========================================================================
    // Simulation Methods (Private Helpers)
    // =========================================================================

    /**
     * Simulate image processing operations.
     */
    private function simulateImageProcessing(string $path): array
    {
        // Simulate: load, resize, filter, compress
        $this->cpuWork(150); // ~150ms of CPU work

        return [
            'path' => $path,
            'thumbnail' => str_replace('.jpg', '_thumb.jpg', $path),
            'size' => rand(10000, 50000),
        ];
    }

    /**
     * Simulate data transformation on a chunk.
     */
    private function transformDataChunk(array $data): array
    {
        // Simulate: parse, validate, transform, aggregate
        $result = [];
        foreach ($data as $item) {
            $result[] = $item * 2;
        }
        sort($result);

        // Add some CPU work
        $this->cpuWork(50);

        return array_slice($result, 0, 10); // Return summary
    }

    /**
     * Simulate password hashing (bcrypt).
     */
    private function simulatePasswordHash(string $password): string
    {
        // Simulate bcrypt cost=10 (~200ms)
        return hash('sha256', $password . str_repeat('salt', 1000));
    }

    /**
     * Generate a large JSON payload for testing.
     */
    private function generateLargeJsonPayload(): string
    {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => str_repeat('desc ', 20),
                'metadata' => [
                    'created_at' => date('Y-m-d H:i:s'),
                    'tags' => ['tag1', 'tag2', 'tag3'],
                ],
            ];
        }

        return json_encode($data);
    }

    /**
     * Process JSON data (decode, validate, transform).
     */
    private function processJsonData(string $json): array
    {
        $data = json_decode($json, true);

        // Simulate processing
        $result = array_filter($data, fn ($item) => $item['id'] % 2 === 0);

        // Add CPU work
        $this->cpuWork(30);

        return $result;
    }

    /**
     * Simulate report generation.
     */
    private function generateReport(array $config): array
    {
        // Simulate: query data, calculate metrics, format output
        $this->cpuWork(300); // ~300ms of processing

        return [
            'type' => $config['type'],
            'range' => $config['range'],
            'metrics' => [
                'total' => rand(1000, 10000),
                'average' => rand(100, 1000),
            ],
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Simulate file validation.
     */
    private function validateFile(array $file): array
    {
        // Simulate: checksum, format check, content scan
        $checksum = hash('sha256', $file['content']);
        $this->cpuWork(50);

        return [
            'name' => $file['name'],
            'valid' => true,
            'checksum' => $checksum,
            'size' => $file['size'],
        ];
    }
}
