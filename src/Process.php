<?php

namespace PE\Component\Process;

class Process implements ProcessInterface
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * Signals constructor.
     */
    public function __construct()
    {
        $this->handlers = [];

        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    public function fork()
    {
        // TODO: Implement fork() method.
    }

    public function kill()
    {
        // TODO: Implement kill() method.
    }

    /**
     * @return $this
     */
    public function dispatch()
    {
        pcntl_signal_dispatch();

        foreach ($this->queue as $signal) {
            foreach ($this->handlers[$signal] as $callable) {
                $callable($signal);
            }
        }

        return $this;
    }
}