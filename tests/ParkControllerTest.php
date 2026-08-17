<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Park;
use App\Factory\ParkFactory;
use App\Repository\ParkRepository;
use App\Validator\UniquePark;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ParkControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ParkFactory $factory;
    private ParkRepository $repository;

    /** @return Park[] */
    public function createPark(string $name, int $amount = 1): array
    {
        $parks = [];
        for ($i = 1; $i <= $amount; $i++) {
            $park = $this->factory->create($name);
            $this->entityManager->persist($park);
            $parks[] = $park;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $parks;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->factory = static::getContainer()->get(ParkFactory::class);
        $this->repository = $this->entityManager->getRepository(Park::class);
    }

    #[Test]
    public function canFetchASingleParkBySlug(): void
    {
        $this->createPark('Witterzomer');

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

    #[Test]
    public function canCreateNewPark(): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => 'Witterzomer',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(1, $this->repository->count(['slug' => 'witterzomer']));
        self::assertResponseHeaderSame('Location', '/parks/witterzomer');
    }

    #[Test]
    public function canCreateParkWithPool(): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => 'Witterzomer',
            'hasPool' => true,
        ]);
        self::assertResponseStatusCodeSame(201);
        $park = $this->repository->findOneBy(['slug' => 'witterzomer']);

        self::assertNotNull($park);
        self::assertSame('Witterzomer', $park->getName());
        self::assertTrue($park->hasPool());
    }

    #[Test]
    public function canNotCreateParkWithInvalidInputForHavingAnPool(): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => 'Witterzomer',
            'hasPool' => 'ja',
        ]);
        self::assertResponseIsUnprocessable();
        $errors = HttpErrorResponse::make((string) $this->client->getResponse()->getContent());

        self::assertSame(
            'This value should be of type {{ type }}.', $errors->violations[0]->template
        );
    }

    #[Test]
    public function canNotCreateDuplicateParks(): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => 'Witterzomer',
        ]);
        $this->client->jsonRequest('POST', '/parks', [
            'name' => 'Witterzomer',
        ]);

        self::assertResponseIsUnprocessable();

        $errors = HttpErrorResponse::make((string) $this->client->getResponse()->getContent());

        self::assertSame('urn:uuid:' . UniquePark::PARK_NOT_UNIQUE_ERROR, $errors->violations[0]->type);

        self::assertSame(1, $this->repository->count());
    }

    public static function generateTooShortOrBlankParkName(): \Generator
    {
        yield 'blank park name' => ['', NotBlank::IS_BLANK_ERROR];
        yield 'not empty park name' => ['W', Length::TOO_SHORT_ERROR];
        yield 'park name is too long' => [str_repeat('Witterzomer', 100), Length::TOO_LONG_ERROR];
    }

    #[Test]
    #[DataProvider('generateTooShortOrBlankParkName')]
    public function shouldHaveParkNameWithReasonableLength(string $value, string $uuid): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => $value,
        ]);

        self::assertResponseIsUnprocessable();

        $errors = HttpErrorResponse::make((string) $this->client->getResponse()->getContent());

        $violation = array_filter($errors->violations, fn (HttpValidationError $validationError) => $validationError->type === 'urn:uuid:' . $uuid);
        self::assertCount(1, $violation);
    }

    #[Test]
    public function throwsTypeErrorWithNullValue(): void
    {
        $this->client->jsonRequest('POST', '/parks', [
            'name' => null,
        ]);

        self::assertResponseIsUnprocessable();

        $errors = HttpErrorResponse::make((string) $this->client->getResponse()->getContent());

        self::assertSame(
            'This value should be of type {{ type }}.', $errors->violations[0]->template
        );
    }

    #[Test]
    public function throwsUnsupportedMediaTypeWithUnknownContentType(): void
    {
        $this->client->request('POST', '/parks', [
            'name' => 'Witterzomer',
        ], server: ['CONTENT_TYPE' => 'application/some-random-type']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    #[Test]
    public function throwsBadRequestErrorWhenInvalidJsonIsGiven(): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $this->client->request('POST', '/parks', server: $server, content: '{"name": "}');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function indexRouteShowsMaxTwentyFiveResult(): void
    {
        for ($i = 1; $i < 30; ++$i) {
            $this->createPark('Witterzomer-' . $i);
        }

        $response = $this->client->jsonRequest('GET', '/parks?page=1');
        $content = (string) $this->client->getResponse()->getContent();

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSelectorCount(25, 'li');
        self::assertSame($response->filter('[data-slug="witterzomer-4"]')->text(), 'Witterzomer-4');
        self::assertStringContainsString('25 van 29', $content);
        self::assertStringContainsString('Page: 1 / 2', $content);
    }
}
