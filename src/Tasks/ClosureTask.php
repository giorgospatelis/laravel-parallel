<?php

declare(strict_types=1);

namespace LaravelParallel\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Closure;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * A task that executes a closure in a parallel worker.
 *
 * This class wraps a closure to make it serializable and executable
 * in a separate worker process via amphp/parallel. It uses Laravel's
 * SerializableClosure to handle closure serialization.
 *
 * @extends AbstractTask<mixed, mixed, mixed>
 */
final class ClosureTask extends AbstractTask
{
    private SerializableClosure $serializable;

    /**
     * Create a new ClosureTask instance.
     *
     * @param  Closure  $closure  The closure to execute in parallel
     */
    public function __construct(Closure $closure)
    {
        parent::__construct();
        $this->serializable = new SerializableClosure($closure);
    }

    /**
     * Custom serialization to handle both parent ID and SerializableClosure.
     */
    public function __serialize(): array
    {
        return [
            'parent' => parent::__serialize(),
            'serializable' => $this->serializable,
        ];
    }

    /**
     * Custom unserialization to restore both parent ID and SerializableClosure.
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data['parent'] ?? []);
        $this->serializable = $data['serializable'];
    }

    /**
     * Execute the closure in the worker process.
     *
     * This method is called by amphp/parallel when the task is executed
     * in a worker process. It unwraps the serialized closure and executes it.
     *
     * @param  Channel<mixed, mixed>  $channel  The communication channel (unused)
     * @param  Cancellation  $cancellation  Cancellation token for the task
     * @return mixed The result of the closure execution
     */
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $closure = $this->serializable->getClosure();

        return $closure();
    }
}
