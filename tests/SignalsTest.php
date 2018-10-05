<?php

namespace PETest\Component\Process;

use PE\Component\Process\Signals;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class SignalsTest extends TestCase
{
    use PHPMock;

    /**
     * @runInSeparateProcess
     */
    public function testDispatch()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal_dispatch')
            ->expects(static::once());

        (new Signals())->dispatch();
    }

    /**
     * @runInSeparateProcess
     *
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterInvalidHandler()
    {
        (new Signals())->registerHandler(1, new \stdClass());
    }

    /**
     * @runInSeparateProcess
     *
     * @expectedException \RuntimeException
     */
    public function testRegisterHandlerEmulatePCNTLError()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects(static::once())
            ->willReturn(false);

        (new Signals())->registerHandler(1, function(){});
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterHandler()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects(static::once())
            ->willReturn(true);

        (new Signals())->registerHandler(1, function(){});
    }

    /**
     * @runInSeparateProcess
     */
    public function testDispatchRegisteredHandler()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal')
            ->expects(static::once())
            ->willReturnCallback(function ($signal, $callable) {
                $callable($signal);
                return true;
            });

        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_signal_dispatch')
            ->expects(static::once());

        $dispatched = false;

        $signals = new Signals();
        $signals->registerHandler(1, function () use (&$dispatched) {
            $dispatched = true;
        });

        $signals->dispatch();
    }
}
