<?php

namespace PE\Component\Process;

interface ProcessInterface
{
    public function fork();

    public function kill();

    public function dispatch();
}