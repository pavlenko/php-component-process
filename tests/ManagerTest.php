<?php

namespace PETest\Component\Process;

use PE\Component\Process\Manager;
use PE\Component\Process\POSIX;
use PE\Component\Process\Process;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testForkFailed()
    {
        $this->expectException(\RuntimeException::class);

        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::once())->method('fork')->willReturn(-1);

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->fork(new Process(function(){}));
    }

    public function testForkParent()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::once())->method('fork')->willReturn(1000);

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->fork($process = new Process(function(){}));

        static::assertEquals(1000, $process->getPID());
    }

    public function testForkChild()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::once())->method('fork')->willReturn(0);
        $posix->expects(static::once())->method('getMyPID');

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->fork(new Process(function(){}));
    }

    public function testWaitNoChildren()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::never())->method('dispatchSignals');

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->wait();
    }

    /**
     * @-runInSeparateProcess
     */
    public function testWaitChildren()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->getMockBuilder(POSIX::class)
            ->disableOriginalConstructor()
            ->setMethods(['fork', 'signalRegister', 'signalDispatch', 'waitPID'])
            ->getMock();

        $posix->expects(static::atLeastOnce())->method('fork')->willReturn(1000);

        $posix->expects(static::atLeastOnce())->method('signalRegister')->willReturnCallback(function ($signal, $callable) {
            $callable($signal);
            return true;
        });

        $posix->expects(static::atLeastOnce())->method('signalDispatch');

        $waitPID = 1000;
        $posix->expects(static::atLeastOnce())->method('waitPID')->willReturnCallback(function () use (&$waitPID) {
            $return  = $waitPID;
            $waitPID = 0;
            return $return;
        });

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->fork(new Process(function(){}));

        static::assertEquals(1, $manager->countChildren());

        $manager->wait();

        static::assertEquals(0, $manager->countChildren());
    }

    public function testDispatch()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);
        $posix->expects(static::once())->method('dispatchSignals');

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->dispatch();
    }

    public function testCountChildren()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->createMock(POSIX::class);

        $pid = 1000;

        $posix
            ->expects(static::atLeastOnce())
            ->method('fork')
            ->willReturnCallback(function() use (&$pid) {
                return $pid++;
            });

        POSIX::setInstance($posix);

        $manager = new Manager();

        static::assertEquals(0, $manager->countChildren());

        $manager->fork((new Process(function(){}))->setAlias('foo'));
        $manager->fork((new Process(function(){}))->setAlias('bar'));

        static::assertEquals(2, $manager->countChildren());
        static::assertEquals(1, $manager->countChildren('foo'));
    }

    public function testShouldTerminate()
    {
        /* @var $posix POSIX|MockObject */
        $posix = $this->getMockBuilder(POSIX::class)
            ->disableOriginalConstructor()
            ->setMethods(['fork', 'kill', 'signalRegister', 'signalDispatch', 'waitPID'])
            ->getMock();

        $posix->expects(static::once())->method('fork')->willReturn(1000);

        $posix->expects(static::atLeastOnce())->method('signalRegister')->willReturnCallback(function ($signal, $callable) {
            $callable($signal);
            return true;
        });

        $callable = function ($pid, $signal) {
            $this->getQueue()->enqueue($signal);
            return true;
        };

        $callable = \Closure::bind($callable, $posix, POSIX::class);

        $posix->expects(static::atLeastOnce())->method('kill')->willReturnCallback($callable);
        $posix->expects(static::atLeastOnce())->method('waitPID')->willReturnCallback(function () { return 0; });

        POSIX::setInstance($posix);

        $manager = new Manager();
        $manager->fork(new Process(function(){}));

        static::assertFalse($manager->isShouldTerminate());

        $posix->kill(0, POSIX::SIGINT);

        $manager->dispatch();

        static::assertTrue($manager->isShouldTerminate());
    }
}