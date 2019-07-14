<?php

namespace lokothodida\Muqu;

class Message
{
    private $name;
    private $contents;

    public function __construct(string $name, string $contents)
    {
        $this->name = $name;
        $this->contents = $contents;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function contents(): string
    {
        return $this->contents;
    }
}
