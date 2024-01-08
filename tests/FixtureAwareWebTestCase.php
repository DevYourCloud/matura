<?php

namespace App\Tests;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FixtureAwareWebTestCase extends WebTestCase
{
    protected EntityManagerInterface $em;

    protected KernelBrowser $client;

    protected ?ORMExecutor $fixtureExecutor = null;

    protected ?Loader $fixtureLoader = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->fixtureLoader = new Loader();
    }

    /**
     * Adds a new fixture to be loaded.
     */
    protected function addFixture(FixtureInterface $fixture): void
    {
        $this->fixtureLoader->addFixture($fixture);
    }

    /**
     * Executes all the fixtures that have been loaded so far.
     */
    protected function executeFixtures(): void
    {
        $this->getFixtureExecutor()->execute($this->fixtureLoader->getFixtures());
    }

    private function getFixtureExecutor(): ORMExecutor
    {
        if (!$this->fixtureExecutor) {
            $this->fixtureExecutor = new ORMExecutor($this->em, new ORMPurger($this->em));
        }

        return $this->fixtureExecutor;
    }
}
