<?php

namespace PETest\Component\Process;

use PE\Component\Process\Process;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    use PHPMock;

    /**
     * @runInSeparateProcess
     */
    public function testKill()
    {
        $pid = 1000;

        $this->getFunctionMock('PE\\Component\\Process', 'posix_kill')
            ->expects(static::once())
            ->with(static::equalTo($pid), static::equalTo(SIGTERM));

        $process = new Process(function () {});
        $process->setPID($pid);
        $process->kill(SIGTERM);
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
