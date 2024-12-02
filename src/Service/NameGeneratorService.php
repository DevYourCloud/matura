<?php

namespace App\Service;

class NameGeneratorService
{
    public function __construct(private readonly array $names, private readonly array $adjectives)
    {
    }

    public function getRandomName(): string
    {
        if (0 === count($this->names) || 0 === count($this->adjectives)) {
            throw new \LogicException('List of names and adjectives should be given to generator');
        }

        $indexName = rand(0, count($this->names) - 1);
        $indexAdjectives = rand(0, count($this->adjectives) - 1);

        return $this->adjectives[$indexAdjectives].' '.$this->names[$indexName];
    }
}
