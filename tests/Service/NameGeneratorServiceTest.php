<?php

namespace App\Tests\Service;

use App\Service\NameGeneratorService;
use PHPUnit\Framework\TestCase;

class NameGeneratorServiceTest extends TestCase
{
    public function testGeneratorReturnNames(): void
    {
        $names = ['Sacha', 'Pierre', 'Ondine'];
        $adjectives = ['Trainer', 'Champion'];

        $generator = new NameGeneratorService($names, $adjectives);

        self::assertNotEmpty($generator->getRandomName());
        self::assertNotEmpty($generator->getRandomName());
        self::assertNotEmpty($generator->getRandomName());
    }

    public function testGeneratorWithEmptyNames(): void
    {
        $generator = new NameGeneratorService([], ['test']);

        $this->expectException(\LogicException::class);

        $generator->getRandomName();
    }

    public function testGeneratorWithEmptyAdjectives(): void
    {
        $generator = new NameGeneratorService(['test'], []);

        $this->expectException(\LogicException::class);

        $generator->getRandomName();
    }

    public function testGeneratorWithBothEmpty(): void
    {
        $generator = new NameGeneratorService([], []);

        $this->expectException(\LogicException::class);

        $generator->getRandomName();
    }
}
