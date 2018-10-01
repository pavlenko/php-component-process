<?php

namespace PE\Component\Process;

trait EventsTrait
{
    /**
     * @var array
     */
    private $listeners = [];

    /**
     * Attach a callback to event
     *
     * @param string   $name     Event name
     * @param callable $listener Listener callback
     */
    public function on(string $name, callable $listener)
    {
        $eventName = 'event:' . $name;

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @param string $name      Event name
     * @param array  $arguments Arguments for pass to listeners
     */
    public function emit(string $name, array $arguments = [])
    {
        $eventName = 'event:' . $name;

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            \call_user_func_array($listener, $arguments);
        }
    }
}