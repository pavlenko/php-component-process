<?php

namespace PETest\Component\Process;

use PE\Component\Process\Manager;
use PE\Component\Process\Process;
use PE\Component\Process\Signals;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    use PHPMock;

    /**
     * @var Signals|MockObject
     */
    private $signals;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->signals = $this->createMock(Signals::class);
        $this->manager = new Manager($this->signals);
    }

    /**
     * @runInSeparateProcess
     *
     * @expectedException \RuntimeException
     */
    public function testForkFailed()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(-1);

        $this->manager->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkParent()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(1000);

        $this->manager->fork($process = new Process(function(){}));

        static::assertEquals(1000, $process->getPID());
    }

    /**
     * @runInSeparateProcess
     */
    public function testForkChild()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(0);

        $this->manager->fork(new Process(function(){}));
    }

    /**
     * @runInSeparateProcess
     */
    public function testWaitNoChildren()
    {
        $this->signals->expects(static::never())
            ->method('dispatch');

        $this->manager->wait();
    }

    /**
     * @runInSeparateProcess
     */
    public function testWaitChildren()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects(static::once())
            ->willReturnCallback(function ($signal, $callable) {
                $callable($signal);
                return true;
            });

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::once())
            ->willReturn(1000);

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_waitpid')
            ->expects(static::once())
            ->willReturn(1000);

        $manager = new Manager(new Signals());

        $this->manager->fork(new Process(function(){}));

        $manager->wait();
    }

    /**
     * @runInSeparateProcess
     */
    public function testDispatch()
    {
        $this->signals->expects(static::once())
            ->method('dispatch');

        $this->manager->dispatch();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCountChildren()
    {
        static::assertEquals(0, $this->manager->countChildren());

        $pid = 1000;

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects(static::atLeastOnce())
            ->willReturnCallback(function() use (&$pid) {
                return $pid++;
            });

        $this->manager->fork((new Process(function(){}))->setAlias('foo'));
        $this->manager->fork((new Process(function(){}))->setAlias('bar'));

        static::assertEquals(2, $this->manager->countChildren());
        static::assertEquals(1, $this->manager->countChildren('foo'));
    }
}