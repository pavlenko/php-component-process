<?php

namespace PE\Component\Process;

class Process
{
    use TitleTrait;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var Signals
     */
    private $signals;

    /**
     * @var int
     */
    private $pid = 0;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
        $this->signals  = new Signals();
    }

    /**
     * @inheritDoc
     */
    public function getPID()
    {
        return $this->pid;
    }

    /**
     * @inheritDoc
     */
    public function setPID($pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     *
     * @return self
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $callable = $this->callable;
        $callable($this);
    }

    /**
     * @inheritDoc
     */
    public function kill($signal = SIGTERM)
    {
        posix_kill($this->pid, $signal);
        return $this;
    }
}