<?php

use PHPUnit\Framework\TestCase;
use lokothodida\Muqu\{Queue, Message};

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
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }

        rmdir($this->dir);
    }

    public function testItIsEmptyWhenNoMessagesAreEnqueued(): void
    {
        $this->assertTrue($this->queue->isEmpty());
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

    public function testItIsEmptyWhenAllEnqueuedMessagesAreDequeued(): void
    {
        $this->queue->enqueue(new Message('to_be_dequeued', 'To be dequeued'));
        $this->queue->dequeue();

        $this->assertTrue($this->queue->isEmpty());
    }
}
