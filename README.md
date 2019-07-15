# MuQu
[![Build Status](https://travis-ci.com/lokothodida/muqu.svg?branch=master)](https://travis-ci.org/lokothodida/bank)

A simple, flat-file FIFO messaging queue; useful for quickly
prototyping an application that requires a background worker.

## Requirements
* PHP 7.2+

## Basic usage
```php
// worker.php
use lokothodida\Muqu;

$queue = new Muqu\Queue('/var/tmp/'); // point to a readable+writable directory

$queue->on('hello', function (Muqu\Message $message) {
    echo "[*] Received message: " . $message->contents() . "\n";
});

$queue->consume();
```

```php
// app.php
use lokothodida\Muqu;

$queue = new Muqu\Queue('/var/tmp/');
$queue->enqueue(new Muqu\Message('hello', 'Hello, World!'));
```

# API
## Creating a queue
```php
use lokothodida\Muqu;

$queue = new Muqu\Queue('/var/tmp/');
```

## Sending a message
```php
$queue->enqueue(new Muqu\Message('hello', 'Hello, World!'));
```

## Retrieving a message (when there is a message in the queue)
```php
$message = $queue->dequeue();

// $message->name() === 'hello', $message->contents() === 'Hello, World!'
```

## Consuming all messages on the queue (and continuing to wait)
```php
$queue->consume();
```

## Consuming all messages on the queue (until a condition is met)
```php
$queue->on('hello', function (Muqu\Message $message) {
    echo "[*] Received message: " . $message->contents() . "\n";
});

$continue = true;

$queue->on('quit', function (Muqu\Message $message) use (&$continue) {
    echo "[*] Closing..." . $message->contents() . "\n";
    $continue = false;
});

$queue->consume(
    $until = function () use (&$continue): bool {
        return $continue;
    },
    $onError = function (Throwable $error) {
        // handle your exception...
    },
    $loopDelayInMicroseconds = 1000000 // defaults to 1 second
);

// elsewhere...
$queue->enqueue(new Muqu\Message('hello', 'Hello, World!'));
$queue->enqueue(new Muqu\Message('hello', 'Going to close the app now!'));
$queue->enqueue(new Muqu\Message('quit', 'Bye!'));
```

## Clearing the queue
```php
$queue->clear();

// $queue->isEmpty() === true
```
