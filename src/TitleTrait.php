<?php

namespace PE\Component\Process;

trait TitleTrait
{
    /**
     * Sets the process title.
     *
     * @param string $title
     */
    public function setProcessTitle($title)
    {
        if (\function_exists('cli_set_process_title') && PHP_OS !== 'Darwin') {
            cli_set_process_title($title); //PHP >= 5.5.
        } else if(\function_exists('setproctitle')) {
            setproctitle($title); //PECL proctitle
        }
    }

    /**
     * Returns the current process title.
     *
     * @return string|null
     */
    public function getProcessTitle()
    {
        if (\function_exists('cli_set_process_title') && PHP_OS !== 'Darwin') {
            return cli_get_process_title(); //PHP >= 5.5.
        }

        if (stripos(PHP_OS, 'WIN') !== 0) {
            return exec('ps -p ' . getmypid() . ' -o command| tail -1', $out);
        }

        return null;
    }
}