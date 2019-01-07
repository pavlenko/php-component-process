<?php

namespace PETest\Component\Process;

use PE\Component\Process\Daemon;
use PE\Component\Process\POSIX;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DaemonTest extends TestCase
{
    private $pidPath;

    protected function setUp()
    {
        $this->pidPath = __DIR__ . '/tmp.pid';
        @unlink($this->pidPath);
    }

    protected function tearDown()
    {
        @unlink($this->pidPath);
    }

    public function testIsNoRunning()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::never())->method('kill');

        POSIX::setInstance($posix);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->isRunning();
    }

    public function testIsRunning()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::atLeastOnce())->method('fork')->willReturn(1000);
        $posix->expects(static::atLeastOnce())->method('kill')->willReturnCallback(function ($pid, $signal) {
            return !((int) $signal === 0);
        });

        POSIX::setInstance($posix);

        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->start($logger);
        $daemon->isRunning();
    }

    public function testStopWithNoPIDFile()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Stopping daemon...'));
        $logger->expects(static::once())->method('warning')->with(static::equalTo('There is no server process PID file'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        POSIX::setInstance($posix);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->stop($logger);
    }

    public function testStopWithNoProcess()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Stopping daemon...'));
        $logger->expects(static::once())->method('warning')->with(static::stringStartsWith('There is no server process with PID:'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        POSIX::setInstance($posix);

        file_put_contents($this->pidPath, 1000);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->stop($logger);
    }

    public function testStopWithError()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Stopping daemon...'));
        $logger->expects(static::once())->method('error')->with(static::equalTo('Stopping daemon: ERR'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $posix->expects(static::atLeastOnce())->method('kill')->willReturnCallback(function ($pid, $signal) {
            return (int) $signal === 0;
        });

        POSIX::setInstance($posix);

        file_put_contents($this->pidPath, 1000);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->stop($logger);
    }

    public function testStopSuccess()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::atLeastOnce())->method('info')->withConsecutive(
            [static::equalTo('Stopping daemon...')],
            [static::equalTo('Stopping daemon: OK')]
        );

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $posix->expects(static::atLeastOnce())->method('kill')->willReturn(true);

        POSIX::setInstance($posix);

        file_put_contents($this->pidPath, 1000);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->stop($logger);
    }

    public function testStartAlreadyRunning()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Starting daemon...'));
        $logger->expects(static::once())->method('warning')->with(static::stringStartsWith('Server already running with PID:'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $posix->expects(static::atLeastOnce())->method('kill')->willReturnCallback(function ($pid, $signal) {
            return (int) $signal === 0;
        });

        POSIX::setInstance($posix);

        file_put_contents($this->pidPath, 1000);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->start($logger);
    }

    public function testCannotStartNotRunningWithPIDFileExists()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Starting daemon...'));
        $logger->expects(static::once())->method('warning')->with(static::stringStartsWith('Removing PID file for defunct server process'));
        $logger->expects(static::once())->method('error')->with(static::equalTo('Unable to create child process'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $posix->expects(static::atLeastOnce())->method('kill')->willReturnCallback(function ($pid, $signal) {
            return (int) $signal !== 0;
        });

        $posix->expects(static::atLeastOnce())->method('fork')->willReturn(-1);

        POSIX::setInstance($posix);

        file_put_contents($this->pidPath, 1000);

        $daemon = new Daemon(function () {}, $this->pidPath);
        $daemon->start($logger);
    }

    public function testStartChild()
    {
        /* @var $logger LoggerInterface|MockObject */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::once())->method('info')->with(static::equalTo('Starting daemon...'));

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $posix->expects(static::atLeastOnce())->method('fork')->willReturnCallback(function(){
            file_put_contents($this->pidPath, 1000);
            return 0;
        });
        $posix->expects(static::once())->method('setAsSessionLeader');

        POSIX::setInstance($posix);

        $executed = false;
        $callable = function () use (&$executed) { $executed = true; };

        $daemon = new Daemon($callable, $this->pidPath);
        $daemon->start($logger);

        static::assertTrue($executed);
    }
}
