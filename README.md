# Monolog - Logging for PHP [![Build Status]

Monolog sends your logs to files, sockets, inboxes, databases and various
web services. See the complete list of handlers below. Special handlers
allow you to build advanced logging strategies.

This library implements the [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
interface that you can type-hint against in your own libraries to keep
a maximum of interoperability. You can also use it in your applications to
make sure you can always use another compatible logger at a later time.
As of 1.11.0 Monolog public APIs will also accept PSR-3 log levels.
Internally Monolog still uses its own level scheme since it predates PSR-3.

## Installation

Install the latest version with

```bash
$ composer require monolog/monolog
```

## Basic Usage

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

## Documentation

- [Usage Instructions](doc/01-usage.md)




## About

### Requirements

- Monolog 2.x works with PHP 7.2 or above, use Monolog `^1.0` for PHP 5.3+ support.

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/Seldaek/monolog/issues)

### Framework Integrations



### Author

Fazlul Kabir Shohag - <shohag.fks@gmail.com>

### License

Corona Generic API package is licensed under the MIT License

