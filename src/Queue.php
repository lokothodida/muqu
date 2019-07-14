<?php

namespace lokothodida\Muqu;

final class Queue
{
    private $dir;
    private $callbacks = [];

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function enqueue(Message $message): void
    {
        file_put_contents($this->dir . '/' . microtime() . '-'. $message->name() . '.muqu', $message->contents());
    }

    public function dequeue(): Message
    {
        if ($this->isEmpty()) {
            throw new \Exception('Empty queue');
        }

        $messages = glob($this->dir . '*.muqu');
        $first    = $messages[0];
        $name     = basename(substr(strstr($first, '-'), 1), '.muqu');
        $content  = (string) file_get_contents($first);
        $message  = new Message($name, $content);

        unlink($first);

        return $message;
    }

    public function on(string $messageName, callable $callback): void
    {
        if (!isset($this->callbacks[$messageName])) {
            $this->callbacks[$messageName] = [];
        }

        $this->callbacks[$messageName][] = $callback;
    }

    public function consume(?callable $until = null, callable $onError = null, $delayInMicroseconds = 1000000): void
    {
        while (is_null($until) || $until()) {
            try {
                $message = $this->dequeue();
                $this->dispatch($message);
            } catch (\Exception $e) {
                if ($onError) {
                    $onError($e);
                }
            }

            usleep($delayInMicroseconds);
        }
    }

    public function isEmpty(): bool
    {
        return count(glob($this->dir . '*.muqu')) === 0;
    }

    public function clear(): void
    {
        $files = glob($this->dir . '*.muqu');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function dispatch(Message $message): void
    {
        if (isset($this->callbacks[$message->name()])) {
            foreach ($this->callbacks[$message->name()] as $callback) {
                $callback($message);
            }
        }
    }
}
