<?php

namespace PETest\Component\Process;

use PE\Component\Process\Signals;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class SignalsTest extends TestCase
{
    use PHPMock;

    /**
     * @var Signals
     */
    private $signals;

    protected function setUp()
    {
        $this->signals = new Signals();
    }

    public function testDispatch()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal_dispatch')
            ->expects($this->once());

        $this->signals->dispatch();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterInvalidHandler()
    {
        $this->signals->registerHandler(1, new \stdClass());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRegisterHandlerEmulatePCNTLError()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects($this->once())
            ->willReturn(false);

        $this->signals->registerHandler(1, function(){});
    }

    public function testRegisterHandler()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects($this->once())
            ->willReturn(true);

        $this->signals->registerHandler(1, function(){});
    }

    public function testDispatchRegisteredHandler()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects($this->once())
            ->willReturnCallback(function ($signal, $callable) {
                $callable($signal);
                return true;
            });

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal_dispatch')
            ->expects($this->once());

        $dispatched = false;

        $this->signals->registerHandler(1, function () use (&$dispatched) {
            $dispatched = true;
        });

        $this->signals->dispatch();
    }
}
