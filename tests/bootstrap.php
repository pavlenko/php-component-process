<?php

namespace PE\Component\Process {
    class Stubs {
        private static $functions = [];

        public static function call($function, ...$arguments)
        {
            if (array_key_exists($function, static::$functions)) {
                return \call_user_func(static::$functions[$function], ...$arguments);
            }

            throw new \InvalidArgumentException('Unknown stub function ' . $function);
        }

        public static function register($function, callable $callback)
        {}

        public static function remove($function)
        {}
    }

    function pcntl_fork(...$arguments)
    {
        return Stubs::call('pcntl_fork', ...$arguments);
    }

    function pcntl_signal(...$arguments)
    {
        return Stubs::call('pcntl_signal', ...$arguments);
    }

    function pcntl_signal_dispatch(...$arguments)
    {
        return Stubs::call('pcntl_signal_dispatch', ...$arguments);
    }
}

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';

    if (!extension_loaded('pcntl')) {
        define('WNOHANG', 1);
        define('WUNTRACED', 2);
        define('WCONTINUED', 16);
        define('SIG_IGN', 1);
        define('SIG_DFL', 0);
        define('SIG_ERR', -1);
        define('SIGHUP', 1);
        define('SIGINT', 2);
        define('SIGQUIT', 3);
        define('SIGILL', 4);
        define('SIGTRAP', 5);
        define('SIGABRT', 6);
        define('SIGIOT', 6);
        define('SIGBUS', 7);
        define('SIGFPE', 8);
        define('SIGKILL', 9);
        define('SIGUSR1', 10);
        define('SIGSEGV', 11);
        define('SIGUSR2', 12);
        define('SIGPIPE', 13);
        define('SIGALRM', 14);
        define('SIGTERM', 15);
        define('SIGSTKFLT', 16);
        define('SIGCLD', 17);
        define('SIGCHLD', 17);
        define('SIGCONT', 18);
        define('SIGSTOP', 19);
        define('SIGTSTP', 20);
        define('SIGTTIN', 21);
        define('SIGTTOU', 22);
        define('SIGURG', 23);
        define('SIGXCPU', 24);
        define('SIGXFSZ', 25);
        define('SIGVTALRM', 26);
        define('SIGPROF', 27);
        define('SIGWINCH', 28);
        define('SIGPOLL', 29);
        define('SIGIO', 29);
        define('SIGPWR', 30);
        define('SIGSYS', 31);
        define('SIGBABY', 31);
        define('PRIO_PGRP', 1);
        define('PRIO_USER', 2);
        define('PRIO_PROCESS', 0);
        define('SIG_BLOCK', 0);
        define('SIG_UNBLOCK', 1);
        define('SIG_SETMASK', 2);
        define('SI_USER', 0);
        define('SI_KERNEL', 128);
        define('SI_QUEUE', -1);
        define('SI_TIMER', -2);
        define('SI_MESGQ', -3);
        define('SI_ASYNCIO', -4);
        define('SI_SIGIO', -5);
        define('SI_TKILL', -6);
        define('CLD_EXITED', 1);
        define('CLD_KILLED', 2);
        define('CLD_DUMPED', 3);
        define('CLD_TRAPPED', 4);
        define('CLD_STOPPED', 5);
        define('CLD_CONTINUED', 6);
        define('TRAP_BRKPT', 1);
        define('TRAP_TRACE', 2);
        define('POLL_IN', 1);
        define('POLL_OUT', 2);
        define('POLL_MSG', 3);
        define('POLL_ERR', 4);
        define('POLL_PRI', 5);
        define('POLL_HUP', 6);
        define('ILL_ILLOPC', 1);
        define('ILL_ILLOPN', 2);
        define('ILL_ILLADR', 3);
        define('ILL_ILLTRP', 4);
        define('ILL_PRVOPC', 5);
        define('ILL_PRVREG', 6);
        define('ILL_COPROC', 7);
        define('ILL_BADSTK', 8);
        define('FPE_INTDIV', 1);
        define('FPE_INTOVF', 2);
        define('FPE_FLTDIV', 3);
        define('FPE_FLTOVF', 4);
        define('FPE_FLTUND', 7);
        define('FPE_FLTRES', 6);
        define('FPE_FLTINV', 7);
        define('FPE_FLTSUB', 8);
        define('SEGV_MAPERR', 1);
        define('SEGV_ACCERR', 2);
        define('BUS_ADRALN', 1);
        define('BUS_ADRERR', 2);
        define('BUS_OBJERR', 3);
        define('PCNTL_EINTR', 4);
        define('PCNTL_ECHILD', 10);
        define('PCNTL_EINVAL', 22);
        define('PCNTL_EAGAIN', 11);
        define('PCNTL_ESRCH', 3);
        define('PCNTL_EACCES', 13);
        define('PCNTL_EPERM', 1);
        define('PCNTL_ENOMEM', 12);
        define('PCNTL_E2BIG', 7);
        define('PCNTL_EFAULT', 14);
        define('PCNTL_EIO', 5);
        define('PCNTL_EISDIR', 21);
        define('PCNTL_ELIBBAD', 80);
        define('PCNTL_ELOOP', 40);
        define('PCNTL_EMFILE', 24);
        define('PCNTL_ENAMETOOLONG', 36);
        define('PCNTL_ENFILE', 23);
        define('PCNTL_ENOENT', 2);
        define('PCNTL_ENOEXEC', 8);
        define('PCNTL_ENOTDIR', 20);
        define('PCNTL_ETXTBSY', 26);
    }
}