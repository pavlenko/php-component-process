<?php

namespace PE\Component\Process;

class Manager
{
    use TitleTrait;

    /**
     * @var Signals
     */
    private $signals;

    /**
     * @var Process[]
     */
    private $children = [];

    /**
     * @var bool
     */
    private $shouldTerminate = false;

    /**
     * @var int
     */
    private $maxLifeTime = 0;

    /**
     * @var float
     */
    private $startTime;

    /**
     * @var int
     */
    private $maxExecutedProcesses = 0;

    /**
     * @var int
     */
    private $executions = 0;

    /**
     * @var string
     */
    private $terminateReason;

    public function __construct()
    {
        $this->signals = new Signals();
        $this->signals->registerHandler(SIGCHLD, function () { $this->handleChildShutdown(); });
        $this->signals->registerHandler(SIGTERM, function () { $this->handleParentShutdown(); });
        $this->signals->registerHandler(SIGINT, function () { $this->handleParentShutdown(); });
    }

    /**
     * @param int $maxLifeTime
     */
    public function setMaxLifeTime($maxLifeTime)
    {
        $this->maxLifeTime = (int) $maxLifeTime;
    }

    /**
     * @param int $maxExecutedProcesses
     */
    public function setMaxExecutedProcesses($maxExecutedProcesses)
    {
        $this->maxExecutedProcesses = (int) $maxExecutedProcesses;
    }

    /**
     * @return string
     */
    public function getTerminateReason()
    {
        return $this->terminateReason;
    }

    /**
     * @return bool
     */
    public function isShouldTerminate(): bool
    {
        if ($this->shouldTerminate) {
            $this->terminateReason = 'Signal';
            return true;
        }

        if ($this->maxLifeTime > 0 && $this->maxLifeTime < microtime(true) - $this->startTime) {
            $this->terminateReason = 'Lifetime';
            return true;
        }

        if ($this->maxExecutedProcesses > 0 && $this->maxExecutedProcesses <= $this->executions) {
            $this->terminateReason = 'Executions count';
            return true;
        }

        return false;
    }

    /**
     * Handle child shutdown signals
     */
    private function handleChildShutdown()
    {
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            if (isset($this->children[$pid])) {
                unset($this->children[$pid]);
            }
        }
    }

    /**
     * Handle parent process shutdown signals
     */
    private function handleParentShutdown()
    {
        $this->shouldTerminate = true;

        foreach ($this->children as $pid => $process) {
            $process->kill();
            unset($this->children[$pid]);
        }
    }

    /**
     * Internal create process
     *
     * @param callable $callable
     *
     * @return Process
     */
    private function createProcess(callable $callable)
    {
        return new Process($callable);
    }

    /**
     * Create process fork from callable
     *
     * @param callable $callable
     *
     * @return Process
     */
    public function fork(callable $callable)
    {
        $this->internalFork($process = $this->createProcess($callable));
        return $process;
    }

    /**
     * Create new process fork
     *
     * @param Process $process
     */
    private function internalFork(Process $process)
    {
        $this->executions++;

        $pid = pcntl_fork();

        if (-1 === $pid) {
            // Error fork
            throw new \RuntimeException('Failure on pcntl_fork');
        }

        if ($pid) {
            // Parent code
            $this->children[$pid] = $process;

            $process->setPID($pid);
            return;
        }

        // Child code
        $process
            ->setPID(getmypid())
            ->run();

        exit(0);
    }

    /**
     * Wait for children processes completed
     */
    public function wait()
    {
        while (\count($this->children) > 0) {
            $this->dispatch();
        }
    }

    /**
     * Dispatch processes & signals
     */
    public function dispatch()
    {
        $this->signals->dispatch();
        usleep(100000);
    }

    /**
     * Get active children count
     *
     * @return int
     */
    public function countChildren(): int
    {
        return \count($this->children);
    }

    /**
     * @param string
     *
     * @return  int
     */
    public function countChildrenByAlias($alias): int
    {
        $children = array_filter($this->children, function (Process $process) use ($alias) {
            return $process->getAlias() === $alias;
        });

        return \count($children);
    }
}