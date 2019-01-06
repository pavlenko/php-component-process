<?php

namespace PETest\Component\Process;

use PE\Component\Process\Daemon;
use PE\Component\Process\POSIX;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DaemonTest extends TestCase
{
    public function testIsNoRunning()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::never())->method('kill');

        POSIX::setInstance($posix);

        $callable = function () {};
        $pidPath  = __DIR__ . '/tmp.pid';

        $daemon = new Daemon($callable, $pidPath);
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

        $callable = function () {};
        $pidPath  = __DIR__ . '/tmp.pid';

        $logger = new NullLogger();

        $daemon = new Daemon($callable, $pidPath);
        $daemon->start($logger);
        $daemon->isRunning();

        @unlink($pidPath);
    }

    public function testStop()
    {

    }

    public function testGetPID()
    {

    }

    public function testStart()
    {

    }
}
