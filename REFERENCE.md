# Symfony voor de ervaren Laravel-dev

Alleen de verschillen die ertoe doen. Idiomen gelden voor moderne Symfony (7.4 LTS / 8.x).
Je PHP-stijl (`final`, guard clauses, early returns, PHPStan max) past hier één-op-één — Symfony zit er niet mee.

---

## De twee mentale omschakelingen

Alles hieronder valt terug op deze twee. Snap je deze, dan is de rest syntax.

1. **De container is expliciet en compile-time, niet magisch en runtime.**
   Geen facades, geen `app()`, geen service-locator-gedrag in je code. Je type-hint een dependency in je constructor en Symfony wiret 'm automatisch (autowiring). De container wordt *gecompileerd* naar PHP — fouten in bedrading zie je bij build, niet bij een request.

2. **Doctrine is Data Mapper, niet Active Record.**
   Een entity is een dom PHP-object dat niks van de database weet. Geen `$user->save()`, geen `User::where(...)`. Je manipuleert objecten en de `EntityManager` bepaalt wélke queries wanneer draaien (unit of work). Dit is de grootste aanpassing vanuit Eloquent.

---

## Projectstructuur

```
bin/console            # = artisan
config/
  packages/*.yaml      # config per bundle (framework, doctrine, ...)
  routes.yaml          # meestal leeg; routing via attributes
  services.yaml        # DI-config; autowiring staat default aan
public/index.php       # front controller
src/
  Controller/
  Entity/
  Repository/
  Command/
  Kernel.php
migrations/            # Doctrine migrations
templates/             # Twig
.env                   # committen; .env.local voor secrets (gitignored)
```

Geen `app/` met alles erin. Namespace is `App\` → `src/`. PSR-4, strak.

---

## Services & Dependency Injection

Dé kern. In `services.yaml` staat standaard:

```yaml
services:
    _defaults:
        autowire: true       # type-hints worden vanzelf geïnjecteerd
        autoconfigure: true  # tags (commands, subscribers) auto-toegekend

    App\:
        resource: '../src/'
```

Daardoor is een service gewoon een class. Geen registratie nodig:

```php
final class PriceCalculator
{
    public function __construct(
        private readonly TaxRepository $taxes,
        private readonly LoggerInterface $logger,
    ) {}

    public function total(Booking $booking): Money { /* ... */ }
}
```

Type-hint 'm ergens anders in een constructor → hij is er. Geen `bind()`, geen service provider.

**Interfaces binden** (het equivalent van `$this->app->bind(X::class, Y::class)`):

```yaml
services:
    App\Contracts\PaymentGateway: '@App\Payment\MollieGateway'
```

**Scalar/config injecteren** via parameters:

```yaml
parameters:
    valoma.commission_rate: 0.08

services:
    App\Booking\CommissionService:
        arguments:
            $rate: '%valoma.commission_rate%'
```

Wat je kwijtraakt: `app()`, `resolve()`, facades, `config()` als globale helper. Alles gaat via injectie. Dat voelt strikter, en dat is precies het punt — het is expliciet en testbaar.

---

## Routing & Controllers

Routing zit als attribute op de controller. Geen `routes/web.php`.

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParkController extends AbstractController
{
    #[Route('/parks/{slug}', name: 'park_show', methods: ['GET'])]
    public function show(string $slug, ParkRepository $parks): Response
    {
        $park = $parks->findOneBySlug($slug);

        if ($park === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('park/show.html.twig', ['park' => $park]);
    }
}
```

Aandachtspunten t.o.v. Laravel:
- Controllers extenden meestal `AbstractController` (geeft je `render()`, `json()`, `createNotFoundException()`, `getUser()`, ...).
- **Elke actie retourneert een `Response`-object.** Geen impliciete conversie zoals Laravel doet met arrays/strings. Wel `$this->json($data)` voor JSON.
- **Method injection is de norm**, niet de uitzondering. Je type-hint services én route-params door elkaar in de method-signatuur; Symfony matcht op naam (params) en type (services).
- Route-model-binding bestaat als `#[MapEntity]`, maar wees expliciet in een assessment — een repository-call leest duidelijker.

---

## Doctrine (vervangt Eloquent)

### Entity

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParkRepository::class)]
class Park
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    // ...
}
```

Let op: entities zijn hier conventioneel *niet* `final` (Doctrine maakt lazy-loading proxies die eruit erven). Dat botst met je gewoonte. Voor de rest van je services blijf je gewoon `final` gebruiken.

### Lezen & schrijven

Geen static model-calls. Je gaat via de `EntityManager` en repositories.

```php
final class BookPark
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(Park $park, DateRange $range): Booking
    {
        $booking = new Booking($park, $range);

        $this->em->persist($booking); // "volg dit object"
        $this->em->flush();           // NU pas draait de INSERT

        return $booking;
    }
}
```

`persist()` markeert, `flush()` voert uit — vaak in één transactie voor alle pending changes. Voor een bestaand object dat je wijzigt heb je vaak *alleen* `flush()` nodig; Doctrine detecteert de wijziging zelf (change tracking). Dat is even wennen: je roept nergens `save()` aan.

### Repository (= je query-laag)

```php
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Park> */
final class ParkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Park::class);
    }

    public function findOneBySlug(string $slug): ?Park
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /** @return list<Park> */
    public function withPool(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.hasPool = true')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

`findOneBy` / `findBy` / `find` zitten er standaard in. Complexer werk via de QueryBuilder of DQL (lijkt op SQL, praat over entities i.p.v. tabellen). Type-hint `ParkRepository` waar je 'm nodig hebt — autowiring regelt de rest.

### Migrations

```bash
php bin/console make:migration           # genereert diff uit entity-mappings
php bin/console doctrine:migrations:migrate
```

Anders dan Laravel schrijf je migrations meestal niet met de hand: je past de entity aan, `make:migration` genereert de diff. Wel altijd even nalezen wat het genereert.

---

## Console commands (vervangt Artisan)

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'valoma:sync-parks', description: 'Sync park availability')]
final class SyncParksCommand extends Command
{
    public function __construct(
        private readonly ParkSyncService $sync,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->sync->run();

        $io->success("Synced {$count} parks.");

        return Command::SUCCESS;
    }
}
```

`#[AsCommand]` + autoconfigure = geregistreerd, geen kernel-aanpassing. `SymfonyStyle` ($io) is je toolkit voor output, tabellen, vragen, progress bars. Return `Command::SUCCESS` / `FAILURE`.

---

## Requests & validatie (geen FormRequest)

Er is geen FormRequest. De moderne aanpak: een DTO + attribute-constraints, en Symfony deserialiseert + valideert de request-body automatisch met `#[MapRequestPayload]`.

```php
use Symfony\Component\Validator\Constraints as Assert;

final class CreateBookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $parkSlug,

        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly string $checkIn,

        #[Assert\Positive]
        public readonly int $guests,
    ) {}
}
```

```php
#[Route('/bookings', methods: ['POST'])]
public function store(
    #[MapRequestPayload] CreateBookingRequest $request,
): Response {
    // $request is al gevalideerd; faalt validatie → automatisch 422
    // ...
}
```

Dit is de dichtstbijzijnde parallel met FormRequest, maar netter: het is een echt DTO, geen array. Voor losse validatie buiten een request injecteer je `ValidatorInterface` en roep je `$validator->validate($object)` aan.

(Er bestaan ook Symfony Forms — een zwaar systeem voor server-rendered HTML-formulieren. Voor een API of moderne frontend heb je dat meestal niet nodig. Ken het bestaan, reik er niet standaard naar.)

---

## Config & env

- `.env` committen (defaults), `.env.local` voor secrets (gitignored). Symfony leest deze zelf; geen extra package.
- Config per bundle in `config/packages/*.yaml`. Bijv. `doctrine.yaml`, `framework.yaml`.
- Geen globale `config()` helper in je services. Injecteer wat je nodig hebt via parameters (zie DI hierboven) of bind een klein config-object.
- Secrets voor productie: Symfony heeft een ingebouwde secrets-vault (`bin/console secrets:set`), los van `.env.local`.

---

## Twig (vervangt Blade), kort

```twig
{# templates/park/show.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>{{ park.name }}</h1>
    {% for room in park.rooms %}
        <li>{{ room.title }} — {{ room.price|number_format(2) }}</li>
    {% endfor %}
{% endblock %}
```

Vertaling van Blade-reflexen: `@extends` → `{% extends %}`, `@section/@yield` → `{% block %}`, `{{ }}` autoescaped (net als Blade), `|` voor filters i.p.v. Blade-directives, `{{ path('park_show', {slug: park.slug}) }}` voor route-URLs. Conceptueel bekend terrein.

---

## Testing

Default is **PHPUnit**, niet Pest. Pest draait technisch op Symfony, maar in een bestaand/assessment-project ga je uit van PHPUnit tenzij anders vermeld.

- `KernelTestCase` — container beschikbaar, voor service-/integratietests.
- `WebTestCase` — maakt een kernel-client, request/response testen zonder echte HTTP (vergelijkbaar met Laravel's HTTP-tests).

```php
final class ParkControllerTest extends WebTestCase
{
    public function test_show_returns_200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/parks/lauwersmeer');

        self::assertResponseIsSuccessful();
    }
}
```

Voor DB-tests: aparte test-database, transacties terugdraaien per test (bijv. via `dama/doctrine-test-bundle`).

---

## Laravel → Symfony spiekbrief

| Laravel | Symfony |
|---|---|
| `artisan` | `bin/console` |
| Service provider / `bind()` | `services.yaml` + autowiring |
| Facade / `app()` / `resolve()` | constructor-injectie (type-hint) |
| `config('x.y')` | parameters + injectie |
| Eloquent model (Active Record) | Doctrine entity (Data Mapper) |
| `Model::where()->get()` | repository + QueryBuilder/DQL |
| `$model->save()` | `$em->persist()` + `$em->flush()` |
| `routes/web.php` | `#[Route]` attribute op controller |
| `FormRequest` | DTO + `#[Assert\*]` + `#[MapRequestPayload]` |
| Blade | Twig |
| `.env` + `config/` | `.env` + `config/packages/*.yaml` |
| `make:*` (Artisan) | `make:*` (MakerBundle) |
| Pest (default in nieuwe projecten) | PHPUnit (default) |

---

## Wat deze week te oefenen (concreet)

Doel: geen gehaper op framework-reflexen onder tijdsdruk. Niet de concepten leren — die ken je — maar de vingers trainen.

```bash
# vereist: PHP 8.2+ (8.4 voor Symfony 8.x), Composer, Symfony CLI (optioneel maar handig)
symfony new valoma-oefen --webapp   # of: composer create-project symfony/skeleton
```

Bouw in ± een dag een mini-CRUD die precies de gap raakt:

1. `make:entity Park` (naam, slug, hasPool) → bekijk de gegenereerde entity + repository.
2. `make:migration` + `doctrine:migrations:migrate` → zie hoe de diff werkt.
3. `make:controller ParkController` → voeg een index + show toe met repository-injectie.
4. Een POST-endpoint met een DTO + `#[MapRequestPayload]` + `#[Assert\*]` → forceer een 422.
5. Een `make:command valoma:seed-parks` die er een paar wegschrijft via de EntityManager.
6. Eén `WebTestCase` die de index-route op 200 checkt.

Als je die zes stappen vloeiend doet zonder de docs erbij, ben je klaar voor het assessment.
De architectuur- en codekwaliteitskeuzes (waar jij goed in bent) wegen daar zwaarder dan Symfony-parate kennis — maar je wilt niet struikelen over "waar staat dit ook alweer".

**Blijf eerlijk in het gesprek:** je hebt de concepten via Laravel, je hebt deze week de framework-mechaniek geoefend, je hebt geen productie-Symfony gedraaid.
Dat is een sterke, verdedigbare positie. Doe niet alsof je er jaren op zit.
