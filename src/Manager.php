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

    /**
     * @param Signals $signals
     */
    public function __construct(Signals $signals)
    {
        $this->signals = $signals;
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
        $status = null;
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
     * @param Process $process
     */
    public function fork(Process $process)
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
     * @param string $alias
     *
     * @return int
     */
    public function countChildren(string $alias = null): int
    {
        $children = array_filter($this->children, function (Process $process) use ($alias) {
            return !$alias || $process->getAlias() === $alias;
        });

        return \count($children);
    }
}