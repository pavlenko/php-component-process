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
        $manager = new Manager($signals);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(-1);

        $manager->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkParent()
    {
        $signals = $this->createMock(Signals::class);
        $manager = new Manager($signals);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(1000);

        $manager->fork($process = new Process(function(){}));

        static::assertEquals(1000, $process->getPID());
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkChild()
    {
        $signals = $this->createMock(Signals::class);
        $manager = new Manager($signals);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(0);

        $manager->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testWaitNoChildren()
    {
        $signals = $this->createMock(Signals::class);
        $manager = new Manager($signals);

        $signals->expects(static::never())
            ->method('dispatch');

        $manager->wait();
    }

    /**
     * @runInSeparateProcess
     */
    public function testWaitChildren()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects(static::atLeastOnce())
            ->willReturnCallback(function ($signal, $callable) {
                $callable($signal);
                return true;
            });

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal_dispatch')
            ->expects(static::atLeastOnce());

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(1000);

        $waitPID = 1000;
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_waitpid')
            ->expects(static::atLeastOnce())
            ->willReturnCallback(function () use (&$waitPID) {
                $return  = $waitPID;
                $waitPID = 0;
                return $return;
            });

        $manager = new Manager(new Signals());

        $manager->fork(new Process(function(){}));

        static::assertEquals(1, $manager->countChildren());

        $manager->wait();

        static::assertEquals(0, $manager->countChildren());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDispatch()
    {
        $signals = $this->createMock(Signals::class);
        $manager = new Manager($signals);

        $signals->expects(static::once())
            ->method('dispatch');

        $manager->dispatch();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCountChildren()
    {
        $signals = $this->createMock(Signals::class);
        $manager = new Manager($signals);

        static::assertEquals(0, $manager->countChildren());

        $pid = 1000;

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::atLeastOnce())
            ->willReturnCallback(function() use (&$pid) {
                return $pid++;
            });

        $manager->fork((new Process(function(){}))->setAlias('foo'));
        $manager->fork((new Process(function(){}))->setAlias('bar'));

        static::assertEquals(2, $manager->countChildren());
        static::assertEquals(1, $manager->countChildren('foo'));
    }
}