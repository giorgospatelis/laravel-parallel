<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Horizon\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;
use Throwable;

/**
 * API controller for Laravel Parallel metrics in Horizon.
 *
 * Provides HTTP endpoints that return parallel job execution metrics
 * in Horizon-compatible JSON format. These endpoints can be consumed
 * by the Horizon dashboard or custom monitoring tools.
 */
final class ParallelMetricsController
{
    public function __construct(
        private readonly HorizonMetricsBridge $bridge,
    ) {}

    /**
     * Get recent jobs with metadata.
     *
     * Returns a list of recently executed parallel jobs with details:
     * - Task key and name
     * - Worker count used
     * - Execution time
     * - Status (success/failed)
     * - Tags
     * - Timestamp
     *
     * Supports pagination via 'limit' query parameter (default: 100).
     */
    public function recentJobs(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query('limit', 100);

            // Validate limit range
            $limit = max(1, min($limit, 1000));

            $jobs = $this->bridge->getRecentJobs($limit);

            return response()->json($jobs);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overall parallel job statistics.
     *
     * Returns statistics in Horizon dashboard format including:
     * - Failed jobs count
     * - Jobs per minute (throughput)
     * - Active processes
     * - Recent jobs count
     * - Current status
     * - Wait times per worker pool
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->bridge->getStats();

            return response()->json($stats);
        } catch (Throwable $e) {
            return response()->json([
                'failedJobs' => 0,
                'jobsPerMinute' => 0,
                'processes' => 0,
                'recentJobs' => 0,
                'status' => 'error',
                'wait' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active worker processes information.
     *
     * Returns details about currently active worker processes:
     * - Worker ID
     * - Start time
     * - Uptime
     * - Tasks processed
     */
    public function workers(): JsonResponse
    {
        try {
            $workers = $this->bridge->getActiveWorkers();

            return response()->json($workers);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get task workload breakdown.
     *
     * Returns workload information for each task type including:
     * - Task name
     * - Queue length (pending jobs)
     * - Success rate
     * - Average wait time
     */
    public function workload(): JsonResponse
    {
        try {
            $workload = $this->bridge->getWorkload();

            return response()->json($workload);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
