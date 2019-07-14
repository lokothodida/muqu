# MuQu

A simple, flat-file FIFO messaging queue; useful for quickly
prototyping an application that requires a background worker.

## Usage
```php
// worker.php
use lokothodida\Muqu;

$queue = new Muqu\Queue('/var/tmp/');

$queue->on('hello', function (Message $message) {
    echo "[*] Received message: " . $message->contents() . "\n";
});
$queue->consume();

// app.php
use lokothodida\Muqu;

$queue = new Muqu\Queue('/var/tmp/');
$queue->enqueue();
```

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
$continue = true;

$queue->on('quit', function () use (&continue) {
    $continue = false;
});

$queue->consume(function () use (&$continue): bool {
    return $continue;
});
```

## Clearing the queue
```
$queue->clear();

// $queue->isEmpty() === true
```
