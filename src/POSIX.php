<?php

namespace PE\Component\Process;

/**
 * Wrapper around posix and pcntl functions requred by current library
 *
 * @codeCoverageIgnore
 */
class POSIX
{
    const WNOHANG    = 1;
    const WUNTRACED  = 2;
    const WCONTINUED = 16;

    const SIGHUP    = 1;
    const SIGINT    = 2;
    const SIGQUIT   = 3;
    const SIGILL    = 4;
    const SIGTRAP   = 5;
    const SIGABRT   = 6;
    const SIGIOT    = 6;
    const SIGBUS    = 7;
    const SIGFPE    = 8;
    const SIGKILL   = 9;
    const SIGUSR1   = 10;
    const SIGSEGV   = 11;
    const SIGUSR2   = 12;
    const SIGPIPE   = 13;
    const SIGALRM   = 14;
    const SIGTERM   = 15;
    const SIGSTKFLT = 16;
    const SIGCHLD   = 17;
    const SIGCONT   = 18;
    const SIGSTOP   = 19;
    const SIGTSTP   = 20;
    const SIGTTIN   = 21;
    const SIGTTOU   = 22;
    const SIGURG    = 23;
    const SIGXCPU   = 24;
    const SIGXFSZ   = 25;
    const SIGVTALRM = 26;
    const SIGPROF   = 27;
    const SIGWINCH  = 28;
    const SIGPOLL   = 29;
    const SIGIO     = 29;
    const SIGPWR    = 30;
    const SIGSYS    = 31;

    /**
     * @var static
     */
    private static $instance;

    /**
     * @var array[]
     */
    private $handlers = [];

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * Disable constructor.
     */
    protected function __construct()
    {}

    protected function getQueue()
    {
        if ($this->queue === null) {
            $this->queue = new \SplQueue();
            $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
        }

        return $this->queue;
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @param POSIX $instance
     */
    public static function setInstance(POSIX $instance)
    {
        self::$instance = $instance;
    }

    /**
     * @return int
     */
    public function fork(): int
    {
        return pcntl_fork();
    }

    /**
     * @return int
     */
    public function setAsSessionLeader(): int
    {
        return posix_setsid();
    }

    /**
     * @return int
     */
    public function getMyPID(): int
    {
        return posix_getpid();
    }

    /**
     * @param int $pid
     * @param int $status
     * @param int $options
     *
     * @return int
     */
    public function waitPID(int $pid, &$status, int $options = 0): int
    {
        return pcntl_waitpid($pid, $status, $options);
    }

    /**
     * @param int $pid
     * @param int $signal
     *
     * @return bool
     */
    public function kill(int $pid, int $signal): bool
    {
        return posix_kill($pid, $signal);
    }

    /**
     * @param int          $signal
     * @param int|callable $handler
     * @param bool         $restartSystemCalls
     *
     * @return bool
     */
    public function signalRegister(int $signal, $handler, $restartSystemCalls = true): bool
    {
        return pcntl_signal($signal, $handler, $restartSystemCalls);
    }

    /**
     * @return bool
     */
    public function signalDispatch(): bool
    {
        return pcntl_signal_dispatch();
    }

    public function registerSignalHandler(int $signal, callable $handler)
    {
        if (!isset($this->handlers[$signal])) {
            $this->handlers[$signal] = [];

            $result = $this->signalRegister($signal, function ($signal) {
                $this->getQueue()->enqueue($signal);
            });

            if (!$result) {
                throw new \RuntimeException(sprintf('Could not register signal %d with pcntl_signal', $signal));
            }
        }

        $this->handlers[$signal][] = $handler;
    }

    public function dispatchSignals()
    {
        $this->signalDispatch();

        foreach ($this->getQueue() as $signal) {
            foreach ($this->handlers[$signal] as &$callable) {
                $callable($signal);
            }
        }
    }
}