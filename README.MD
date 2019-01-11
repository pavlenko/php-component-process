## Requirements

The following versions of PHP are supported.

* PHP 7.0+

## Installation

To install, use composer:

```
php composer.phar require pe/component-process
```

## Usage

Simple manager usage:

```php
<?php

namespace PE\Component\Process;

// Instantiate manager
$manager = new Manager();

// Create process
$process = new Process(function (Process $process) {
    //TODO do some stuff...
});

// Execute process
$manager->fork($process);
$manager->fork($process);
$manager->fork($process);
$manager->fork($process);// <-- this will be ignored because we set max executed processes

// Wait until processes completed
$manager->wait();
```

Demonize any long executed code:

```php
<?php

namespace PE\Component\Process;

use Psr\Log\NullLogger;

// Define path to pid file, must be writable by php user
$pidPath = sys_get_temp_dir() . '/daemon.pid';

// Instantiate a daemon
$daemon = new Daemon(function () {
    //TODO do some stuff...
}, $pidPath);

// Instantiate logger
$logger = new NullLogger();

// Start execution in background
$daemon->start($logger);

// You can check if daemon is still running by call:
$daemon->isRunning();

// You can break execution by call:
$daemon->stop($logger);
```