<?php

namespace PE\Component\Process;

class Signals
{
    /**
     * @var []
     */
    private $handlers;

    /**
     * @var \SplQueue
     */
    private $queue;

    public function __construct()
    {
        $this->handlers = [];

        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    /**
     * Register given callable with pcntl signal.
     *
     * @param int      $signal  The pcntl signal.
     * @param callback $handler The signal handler.
     *
     * @throws \RuntimeException If could not register handler with pcntl_signal.
     */
    public function registerHandler($signal, $handler)
    {
        if (!\is_callable($handler)) {
            throw new \InvalidArgumentException('The handler is not callable');
        }

        if (!isset($this->handlers[$signal])) {
            $this->handlers[$signal] = [];

            $result = pcntl_signal($signal, function ($signal) {
                $this->queue->enqueue($signal);
            });

            if (!$result) {
                throw new \RuntimeException(sprintf('Could not register signal %d with pcntl_signal', $signal));
            };
        };

        $this->handlers[$signal][] = $handler;
    }

    /**
     * Execute `pcntl_signal_dispatch` and process all registered handlers.
     */
    public function dispatch()
    {
        pcntl_signal_dispatch();

        foreach ($this->queue as $signal) {
            foreach ($this->handlers[$signal] as &$callable) {
                $callable($signal);
            }
        }
    }
}