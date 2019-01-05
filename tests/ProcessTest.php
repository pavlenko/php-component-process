<?php

namespace PETest\Component\Process;

use PE\Component\Process\POSIX;
use PE\Component\Process\Process;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testKill()
    {
        $pid = 1000;

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix
            ->expects(static::once())
            ->method('kill')
            ->with(static::equalTo($pid), static::equalTo(POSIX::SIGTERM));

        POSIX::setInstance($posix);

        $process = new Process(function () {});
        $process->setPID($pid);
        $process->kill(POSIX::SIGTERM);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRun()
    {
        $executed = false;
        $callable = function () use (&$executed) {
            $executed = true;
        };

        $process = new Process($callable);
        $process->run();

        static::assertTrue($executed);
    }
}
