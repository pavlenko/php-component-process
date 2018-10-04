<?php

namespace PETest\Component\Process;

use PE\Component\Process\Manager;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    use PHPMock;

    /**
     * @runInSeparateProcess
     *
     * @expectedException \RuntimeException
     */
    public function testForkFailed()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects($this->once())
            ->willReturn(-1);

        (new Manager())->fork(function(){});
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkParent()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects($this->once())
            ->willReturn(1000);

        $process = (new Manager())->fork(function(){});

        static::assertEquals(1000, $process->getPID());
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkChild()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects($this->once())
            ->willReturn(0);

        (new Manager())->fork(function(){});
    }
}
