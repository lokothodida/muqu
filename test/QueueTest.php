<?php

use PHPUnit\Framework\TestCase;
use lokothodida\Muqu\{Queue, Message, DequeuedEmptyQueue};

final class QueueTest extends TestCase
{
    private $dir;
    private $queue;

    public function setUp(): void
    {
        $this->dir = __DIR__ . '/tmp-' . uniqid();
        mkdir($this->dir);
        $this->queue = new Queue($this->dir);
    }

    public function tearDown(): void
    {
        foreach ((array) glob($this->dir . '/*') as $file) {
            unlink((string) $file);
        }

        rmdir($this->dir);
    }

    public function testItIsEmptyWhenNoMessagesAreEnqueued(): void
    {
        $this->assertTrue($this->queue->isEmpty());
    }

    public function testItErrorsWhenDequeueingAnEmptyQueue(): void
    {
        $this->expectException(DequeuedEmptyQueue::class);
        $this->queue->dequeue();
    }

    public function testItIsNotEmptyWhenMessagesAreEnqueued(): void
    {
        $this->queue->enqueue(new Message('first_message', 'First Message'));
        $this->assertFalse($this->queue->isEmpty());
    }

    public function testItDequeuesEnqueuedMessages(): void
    {
        $this->queue->enqueue(new Message('hello_world', 'Hello, World!'));

        $this->assertEquals(
            new Message('hello_world', 'Hello, World!'),
            $this->queue->dequeue()
        );
    }

    public function testItDequeuesMessagesInFifoOrder(): void
    {
        $this->queue->enqueue(new Message('message_1', ''));
        $this->queue->enqueue(new Message('message_2', ''));
        $this->queue->enqueue(new Message('message_3', ''));

        $this->assertSame('message_1', $this->queue->dequeue()->name());
        $this->assertSame('message_2', $this->queue->dequeue()->name());
        $this->assertSame('message_3', $this->queue->dequeue()->name());
    }

    public function testItIsEmptyWhenAllEnqueuedMessagesAreDequeued(): void
    {
        $this->queue->enqueue(new Message('to_be_dequeued', 'To be dequeued'));
        $this->queue->dequeue();

        $this->assertTrue($this->queue->isEmpty());
    }

    public function testItConsumesEnqueuedMessages(): void
    {
        $continue = true;
        $countedCallbacks = 0;

        $this->queue->enqueue(new Message('to_be_consumed', 'Consume me!'));

        $this->queue->on('to_be_consumed', function (Message $message) use (&$continue, &$countedCallbacks) {
            $this->assertSame('to_be_consumed', $message->name());
            $this->assertSame('Consume me!', $message->contents());
            $continue = false;
            $countedCallbacks++;
        });

        $this->queue->on('to_be_consumed', function (Message $message) use (&$countedCallbacks) {
            $countedCallbacks++;
        });

        $this->queue->consume(function () use (&$continue) {
            return $continue;
        }, $errorHandler = null, $delayInMicroSeconds = 1000);

        $this->assertFalse($continue);
        $this->assertSame(2, $countedCallbacks);
    }

    public function testItUsesErrorHandlerWhenExceptionIsThrownDuringConsumption(): void
    {
        $continue = true;

        $this->queue->enqueue(new Message('will_result_in_error', 'Something bad happened'));

        $this->queue->on('will_result_in_error', function (Message $message) {
            throw new Exception($message->contents());
        });

        $errorHandler = function (Throwable $e) use (&$continue) {
            $this->assertSame('Something bad happened', $e->getMessage());
            $continue = false;
        };

        $this->queue->consume(function () use (&$continue) {
            return $continue;
        }, $errorHandler, $delayInMicroSeconds = 1000);

        $this->assertFalse($continue);
    }

    public function testItClearsAllMessages(): void
    {
        $this->queue->enqueue(new Message('to_be_cleared_1', 'To be cleared'));
        $this->queue->enqueue(new Message('to_be_cleared_2', 'To be cleared'));
        $this->queue->clear();

        $this->assertTrue($this->queue->isEmpty());
    }
}
