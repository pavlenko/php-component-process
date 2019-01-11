<?php

namespace PE\Component\Process;

class Manager
{
    use TitleTrait;

    /**
     * @var Process[]
     */
    private $children = [];

    /**
     * @var bool
     */
    private $shouldTerminate = false;

    public function __construct()
    {
        $posix = POSIX::getInstance();
        $posix->registerSignalHandler(POSIX::SIGCHLD, function () { $this->handleChildShutdown(); });
        $posix->registerSignalHandler(POSIX::SIGTERM, function () { $this->handleParentShutdown(); });
        $posix->registerSignalHandler(POSIX::SIGINT, function () { $this->handleParentShutdown(); });
    }

    /**
     * @return bool
     */
    public function isShouldTerminate(): bool
    {
        if ($this->shouldTerminate) {
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
        while (($pid = POSIX::getInstance()->waitPID(-1, $status, POSIX::WNOHANG)) > 0) {
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
        $pid = POSIX::getInstance()->fork();

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
            ->setPID(POSIX::getInstance()->getMyPID())
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
        POSIX::getInstance()->dispatchSignals();
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