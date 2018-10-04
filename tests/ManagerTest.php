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
     */
    public function testFork()
    {
        $this->getFunctionMock('PE\\Component\\Process', 'pcntl_fork')
            ->expects($this->once())
            ->willReturn(1);

        (new Manager())->fork(function(){});
    }
}
