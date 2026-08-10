<?php

declare(strict_types=1);

namespace App\Tests;

use App\Factory\ParkFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ParkControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ParkFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->factory = static::getContainer()->get(ParkFactory::class);
    }

    #[Test]
    public function canFetchASingleParkBySlug(): void
    {
        $park = $this->factory->create('Witterzomer');
        $this->entityManager->persist($park);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('GET', '/parks/witterzomer');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Park Witterzomer');
    }

    #[Test]
    public function throws404ExceptionWhenNoParkIsFound(): void
    {
        $this->client->request('GET', '/parks/witterzomer');

        self::assertResponseStatusCodeSame(404, 'slug does not exist');
    }
}
