<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

describe('ParallelTestCommand', function () {
    it('is registered as artisan command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('parallel:test');
    });

    it('has correct signature', function () {
        $command = Artisan::all()['parallel:test'];

        expect($command->getName())->toBe('parallel:test')
            ->and($command->getDefinition()->hasOption('workers'))->toBeTrue()
            ->and($command->getDefinition()->hasOption('tasks'))->toBeTrue()
            ->and($command->getDefinition()->hasOption('delay'))->toBeTrue();
    });

    it('has correct description', function () {
        $command = Artisan::all()['parallel:test'];

        expect($command->getDescription())->toBe('Test parallel processing with configurable parameters');
    });

    it('has default option values', function () {
        $command = Artisan::all()['parallel:test'];
        $definition = $command->getDefinition();

        expect($definition->getOption('workers')->getDefault())->toBe('4')
            ->and($definition->getOption('tasks')->getDefault())->toBe('10')
            ->and($definition->getOption('delay')->getDefault())->toBe('100');
    });

    it('executes successfully with minimal tasks', function () {
        $this->artisan('parallel:test', ['--workers' => 1, '--tasks' => 1, '--delay' => 1])
            ->expectsOutput('Running 1 parallel tasks with 1 workers...')
            ->expectsOutputToContain('=== Execution Results ===')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('displays execution results table structure', function () {
        $this->artisan('parallel:test', ['--workers' => 1, '--tasks' => 2, '--delay' => 1])
            ->expectsOutputToContain('=== Execution Results ===')
            ->expectsOutputToContain('Total Tasks')
            ->expectsOutputToContain('Successful')
            ->expectsOutputToContain('Failed')
            ->expectsOutputToContain('Success Rate')
            ->expectsOutputToContain('Total Time')
            ->expectsOutputToContain('Avg Task Time')
            ->expectsOutputToContain('Min Task Time')
            ->expectsOutputToContain('Max Task Time')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('returns success exit code', function () {
        $exitCode = $this->artisan('parallel:test', ['--workers' => 1, '--tasks' => 1, '--delay' => 1]);

        expect($exitCode)->toBe(0);
    })->skip('Requires actual parallel execution');

    it('accepts workers option', function () {
        $this->artisan('parallel:test', ['--workers' => 2, '--tasks' => 1, '--delay' => 1])
            ->expectsOutput('Running 1 parallel tasks with 2 workers...')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('accepts tasks option', function () {
        $this->artisan('parallel:test', ['--workers' => 1, '--tasks' => 3, '--delay' => 1])
            ->expectsOutput('Running 3 parallel tasks with 1 workers...')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('accepts delay option', function () {
        $this->artisan('parallel:test', ['--workers' => 1, '--tasks' => 1, '--delay' => 50])
            ->expectsOutput('Running 1 parallel tasks with 1 workers...')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('accepts all options together', function () {
        $this->artisan('parallel:test', [
            '--workers' => 2,
            '--tasks' => 2,
            '--delay' => 10,
        ])
            ->expectsOutput('Running 2 parallel tasks with 2 workers...')
            ->assertExitCode(0);
    })->skip('Requires actual parallel execution');

    it('can be instantiated directly', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();

        expect($command)->toBeInstanceOf(Illuminate\Console\Command::class);
    });

    it('has correct command signature property', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();

        expect($command->getName())->toBe('parallel:test');
    });

    it('has description property set', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();

        expect($command->getDescription())->toBe('Test parallel processing with configurable parameters');
    });

    it('defines workers option in signature', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('workers'))->toBeTrue()
            ->and($definition->getOption('workers')->getDefault())->toBe('4');
    });

    it('defines tasks option in signature', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('tasks'))->toBeTrue()
            ->and($definition->getOption('tasks')->getDefault())->toBe('10');
    });

    it('defines delay option in signature', function () {
        $command = new LaravelParallel\Console\ParallelTestCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('delay'))->toBeTrue()
            ->and($definition->getOption('delay')->getDefault())->toBe('100');
    });
});
