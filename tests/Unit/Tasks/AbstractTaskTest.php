<?php

declare(strict_types=1);

use LaravelParallel\Tasks\AbstractTask;
use LaravelParallel\Contracts\TaskContract;

beforeEach(function () {
    $this->task = new class extends AbstractTask
    {
        public function run(\Amp\Sync\Channel $channel, \Amp\Cancellation $cancellation): mixed
        {
            return 'test result';
        }
    };
});

it('implements TaskContract', function () {
    expect($this->task)->toBeInstanceOf(TaskContract::class);
});

it('is serializable by default', function () {
    expect($this->task->isSerializable())->toBeTrue();
});

it('generates unique ID lazily', function () {
    $id = $this->task->getId();

    expect($id)->toBeString()
        ->toStartWith('task_');
});

it('returns same ID on multiple calls', function () {
    $id1 = $this->task->getId();
    $id2 = $this->task->getId();

    expect($id1)->toBe($id2);
});

it('generates different IDs for different instances', function () {
    $task2 = new class extends AbstractTask
    {
        public function run(\Amp\Sync\Channel $channel, \Amp\Cancellation $cancellation): mixed
        {
            return 'test result 2';
        }
    };

    expect($this->task->getId())->not->toBe($task2->getId());
});

it('preserves ID across serialization when ID was accessed', function () {
    $originalId = $this->task->getId();

    $data = $this->task->__serialize();

    expect($data)->toHaveKey('id')
        ->and($data['id'])->toBe($originalId);
});

it('handles serialization when ID was not accessed', function () {
    $data = $this->task->__serialize();

    expect($data)->toHaveKey('id')
        ->and($data['id'])->toBeNull();
});

it('supports isSerializable method', function () {
    expect($this->task->isSerializable())->toBeTrue();
});

it('supports custom ID generation', function () {
    $customTask = new class extends AbstractTask
    {
        protected function generateId(): string
        {
            return 'custom_id_123';
        }

        public function run(\Amp\Sync\Channel $channel, \Amp\Cancellation $cancellation): mixed
        {
            return 'result';
        }
    };

    expect($customTask->getId())->toBe('custom_id_123');
});
