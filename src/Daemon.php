<?php

namespace PE\Component\Process;

use Psr\Log\LoggerInterface;

class Daemon
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * Path to pid file for daemon mode
     *
     * @var string
     */
    private $pidFile;

    /**
     * @param callable $callable
     * @param string   $pidFile
     */
    public function __construct(callable $callable, string $pidFile)
    {
        $this->callable = $callable;
        $this->pidFile  = $pidFile;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function start(LoggerInterface $logger)
    {
        $logger->info('Starting daemon...');

        $posix = POSIX::getInstance();

        if (is_file($this->pidFile)) {
            $pid = (int) file_get_contents($this->pidFile);

            if ($posix->kill($pid, 0)) {
                $logger->warning("Server already running with PID: {$pid}");
                return;
            }

            $logger->warning("Removing PID file for defunct server process {$pid}");
            unlink($this->pidFile);
        }

        // @codeCoverageIgnoreStart
        if (!($fh = fopen($this->pidFile, 'wb'))) {
            $logger->error("Unable to open PID file {$this->pidFile} for writing...");
            return;
        }
        // @codeCoverageIgnoreEnd

        $pid = $posix->fork();

        if (-1 === $pid) {
            // Cannot create child
            $logger->error('Unable to create child process');
            return;
        }

        if ($pid) {
            // Parent thread
            fwrite($fh, $pid);
            fclose($fh);
            $logger->info("Starting daemon: OK, PID = {$pid}");
        } else {
            // Child thread
            $posix->setAsSessionLeader();
            call_user_func($this->callable);
            @unlink($this->pidFile);
        }
    }

    /**
     * @param LoggerInterface $logger
     */
    public function stop(LoggerInterface $logger)
    {
        $logger->info('Stopping daemon...');

        $posix = POSIX::getInstance();

        if (is_file($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);

            if (!$posix->kill($pid, 0)) {
                $logger->warning("There is no server process with PID: {$pid}");
                return;
            }

            if ($posix->kill($pid, POSIX::SIGTERM)) {
                $logger->info('Stopping daemon: OK');
            } else {
                $logger->error('Stopping daemon: ERR');
            }
        } else {
            $logger->warning('There is no server process PID file');
        }
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return ($pid = $this->getPID()) && POSIX::getInstance()->kill($pid, 0);
    }

    /**
     * @return false|string
     */
    public function getPID()
    {
        if (is_file($this->pidFile)) {
            return file_get_contents($this->pidFile) ?: false;
        }

        return false;
    }
}