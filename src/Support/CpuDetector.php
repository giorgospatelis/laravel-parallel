<?php

declare(strict_types=1);

namespace LaravelParallel\Support;

use Illuminate\Support\Facades\Log;
use LaravelParallel\Exceptions\ParallelException;

/**
 * Detects the number of CPU cores available on the system.
 *
 * This class provides platform-specific CPU core detection for Linux, macOS,
 * BSD, and Windows systems. Results are cached to avoid repeated system calls.
 */
final class CpuDetector
{
    /**
     * Cached CPU core count to avoid repeated detection.
     */
    private static ?int $cachedCpuCores = null;

    /**
     * Clear the cached CPU core count.
     *
     * This method is primarily useful for testing purposes.
     */
    public static function clearCache(): void
    {
        self::$cachedCpuCores = null;
    }

    /**
     * Detect the number of CPU cores available.
     *
     * This method attempts to detect CPU cores using platform-specific methods.
     * Results are cached to avoid repeated system calls.
     *
     * @throws ParallelException If CPU detection fails
     */
    public function detect(): int
    {
        if (self::$cachedCpuCores !== null) {
            return self::$cachedCpuCores;
        }

        $cores = $this->detectCores();

        if ($cores === null || $cores < 1) {
            if (config('parallel.logging.enabled', true)) {
                Log::channel(config('parallel.logging.channel', 'stack'))
                    ->error('CPU core detection failed', [
                        'os_family' => PHP_OS_FAMILY,
                        'php_version' => PHP_VERSION,
                    ]);
            }
            throw ParallelException::cpuDetectionFailed();
        }

        self::$cachedCpuCores = $cores;

        if (config('parallel.logging.enabled', true)) {
            Log::channel(config('parallel.logging.channel', 'stack'))
                ->debug('CPU cores detected', [
                    'cores' => $cores,
                    'os_family' => PHP_OS_FAMILY,
                ]);
        }

        return $cores;
    }

    /**
     * Detect CPU cores using various methods based on OS.
     */
    private function detectCores(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'BSD' || PHP_OS_FAMILY === 'Darwin') {
            $cores = $this->detectCoresUnix();
            if ($cores !== null) {
                return $cores;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $cores = $this->detectCoresWindows();
            if ($cores !== null) {
                return $cores;
            }
        }

        return $this->detectCoresFromEnv();
    }

    /**
     * Detect CPU cores from environment variables.
     */
    private function detectCoresFromEnv(): ?int
    {
        $envVars = ['NUMBER_OF_PROCESSORS', 'NPROCESSORS_ONLN'];

        foreach ($envVars as $var) {
            $value = getenv($var);
            if ($value !== false) {
                $cores = (int) $value;
                if ($cores > 0) {
                    return $cores;
                }
            }
        }

        return null;
    }

    /**
     * Detect CPU cores on Unix-like systems (Linux, macOS, BSD).
     *
     * Security Note: This method uses safe alternatives to shell_exec()
     * to prevent command injection vulnerabilities.
     */
    private function detectCoresUnix(): ?int
    {
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $count = mb_substr_count($cpuinfo, 'processor');
                if ($count > 0) {
                    return $count;
                }
            }
        }

        $cores = $this->execCommandSafely(['sysctl', '-n', 'hw.ncpu']);
        if ($cores !== null && $cores > 0) {
            return $cores;
        }

        $cores = $this->execCommandSafely(['nproc']);
        if ($cores !== null && $cores > 0) {
            return $cores;
        }

        return null;
    }

    /**
     * Detect CPU cores on Windows systems.
     *
     * Security Note: Prioritizes environment variable over shell commands
     * to avoid command injection vulnerabilities.
     */
    private function detectCoresWindows(): ?int
    {
        $cores = getenv('NUMBER_OF_PROCESSORS');
        if ($cores !== false) {
            $cores = (int) $cores;
            if ($cores > 0) {
                return $cores;
            }
        }

        $cores = $this->execCommandSafely(['wmic', 'cpu', 'get', 'NumberOfCores']);
        if ($cores !== null && $cores > 0) {
            return $cores;
        }

        return null;
    }

    /**
     * Execute a command safely using proc_open to prevent command injection.
     *
     * This method uses proc_open() with an array of arguments, which prevents
     * shell interpretation and command injection vulnerabilities.
     *
     * @param  array<string>  $command  Command and arguments as an array
     * @return int|null The number of CPU cores, or null if detection failed
     */
    private function execCommandSafely(array $command): ?int
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        // proc_open with array prevents shell interpretation (secure)
        $process = @proc_open($command, $descriptorspec, $pipes);

        if (! is_resource($process)) {
            return null;
        }

        // Close stdin
        fclose($pipes[0]);

        // Read stdout
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Close stderr
        fclose($pipes[2]);

        // Wait for process to finish and get exit code
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $output === false) {
            return null;
        }

        // Parse output
        $lines = array_filter(array_map('trim', explode("\n", trim($output))));

        if (empty($lines)) {
            return null;
        }

        // For wmic, skip header and get the value
        if ($command[0] === 'wmic' && count($lines) >= 2) {
            $cores = (int) $lines[1];

            return $cores > 0 ? $cores : null;
        }

        // For other commands (nproc, sysctl), first line is the value
        $cores = (int) $lines[0];

        return $cores > 0 ? $cores : null;
    }
}
