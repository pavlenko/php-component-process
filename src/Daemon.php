<?php

namespace PE\Component\Process;

use Psr\Log\LoggerInterface;

class Daemon
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $pidFilePath;

    /**
     * @param Process $process
     */
    public function __construct(Process $process, $pidFilePath)
    {
        $this->process     = $process;
        $this->pidFilePath = $pidFilePath;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function start(LoggerInterface $logger)
    {
        $logger->info('Starting daemon...');

        if (is_file($this->pidFilePath)) {
            $pid = file_get_contents($this->pidFilePath);

            if (posix_kill($pid, 0)) {
                $logger->warning("Daemon already running with PID: {$pid}");
                return;
            }

            $logger->warning("Removing PID file for defunct daemon process {$pid}");
            unlink($this->pidFilePath);
        }

        if (!($fh = fopen($this->pidFilePath, 'wb'))) {
            $logger->error("Unable to open PID file {$this->pidFilePath} for writing...");
            return;
        }

        $pid = pcntl_fork();

        if (-1 === $pid) {
            // Cannot create child
            $logger->error('Unable to create child process');
            exit(1);
        }

        if ($pid) {
            // Parent thread
            fwrite($fh, $pid);
            fclose($fh);
            $logger->info("Starting daemon: OK, PID = {$pid}");
        } else {
            // Child thread
            posix_setsid();
            $this->runner->run();
            unlink($this->pidFilePath);
        }
    }

    /**
     * @param LoggerInterface $logger
     */
    public function stop(LoggerInterface $logger)
    {
        $logger->info('Stopping daemon...');

        if (is_file($this->pidFilePath)) {
            $pid = @file_get_contents($this->pidFilePath);

            if (!posix_kill($pid, 0)) {
                $logger->warning("There is no daemon process with PID: {$pid}");
                return;
            }

            if (posix_kill($pid, SIGTERM)) {
                $logger->info('Stopping daemon: OK');
            } else {
                $logger->error('Stopping daemon: ERR');
            }
        } else {
            $logger->warning('There is no daemon PID file');
        }
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        if ($pid = $this->getPID()) {
            exec("ps -p {$pid} | grep php", $output);

            return implode('', $output) !== '';
        }

        return false;
    }

    /**
     * @return false|string
     */
    public function getPID()
    {
        if (is_file($this->pidFilePath)) {
            return file_get_contents($this->pidFilePath) ?: false;
        }

        return false;
    }
}