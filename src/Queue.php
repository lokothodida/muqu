<?php

namespace lokothodida\Muqu;

final class Queue
{
    /**
     * @var string
     */
    private $dir;

    /**
     * @var callable[][]
     */
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
            throw new DequeuedEmptyQueue();
        }

        $messages = $this->getMuquFilenames();
        $filename = $messages[0];
        $name     = $this->extractMessageNameFromFilename($filename);
        $content  = (string) file_get_contents($filename);
        $message  = new Message($name, $content);
        unlink($filename);

        return $message;
    }

    public function on(string $messageName, callable $callback): void
    {
        if (!isset($this->callbacks[$messageName])) {
            $this->callbacks[$messageName] = [];
        }

        $this->callbacks[$messageName][] = $callback;
    }

    public function consume(callable $until = null, callable $onError = null, $delayInMicroseconds = 1000000): void
    {
        while (is_null($until) || $until()) {
            try {
                $message = $this->dequeue();
                $this->dispatch($message);
            } catch (\Exception $e) {
                $onError && $onError($e);
            }

            usleep($delayInMicroseconds);
        }
    }

    public function isEmpty(): bool
    {
        return count($this->getMuquFilenames()) === 0;
    }

    public function clear(): void
    {
        foreach ($this->getMuquFilenames() as $file) {
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

    private function getMuquFilenames(): array
    {
        return (array) glob($this->dir . '/*.muqu');
    }

    private function extractMessageNameFromFilename(string $filename): string
    {
        $basename = basename($filename, '.muqu');
        $withoutPrefix = (string) strstr($basename, '-');

        return substr($withoutPrefix, 1);
    }
}
