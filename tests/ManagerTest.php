<?php

namespace PETest\Component\Process;

use PE\Component\Process\Manager;
use PE\Component\Process\Process;
use PE\Component\Process\Signals;
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
        $signals = $this->createMock(Signals::class);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(-1);

        (new Manager($signals))->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkParent()
    {
        $signals = $this->createMock(Signals::class);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(1000);

        (new Manager($signals))->fork($process = new Process(function(){}));

        static::assertEquals(1000, $process->getPID());
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkChild()
    {
        $signals = $this->createMock(Signals::class);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(0);

        (new Manager($signals))->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testDispatch()
    {
        $signals = $this->createMock(Signals::class);
        $signals->expects(static::once())
            ->method('dispatch');

        (new Manager($signals))->dispatch();
    }
}
