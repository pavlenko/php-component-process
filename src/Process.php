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
     * @var int
     */
    private $pid = 0;

    /**
     * @var string
     */
    private $alias = '';

    /**
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @return int
     */
    public function getPID(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     *
     * @return static
     */
    public function setPID(int $pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     *
     * @return static
     */
    public function setAlias(string $alias)
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
     * @param int $signal
     *
     * @return static
     */
    public function kill(int $signal = POSIX::SIGTERM)
    {
        POSIX::getInstance()->kill($this->pid, $signal);
        return $this;
    }
}