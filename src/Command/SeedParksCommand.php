<?php

declare(strict_types=1);

namespace App\Command;

use App\Action\GenerateSlug;
use App\Factory\ParkFactory;
use App\Repository\ParkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsCommand(
    name: 'valoma:seed-parks',
    description: 'Seed a few parks to the database.',
)]
#[When(env: 'dev')]
final class SeedParksCommand extends Command
{
    private readonly Generator $faker;

    public function __construct(
        private readonly ParkFactory $factory,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParkRepository $repository,
        private readonly GenerateSlug $generateSlug,
    ) {
        parent::__construct();

        $this->faker = Factory::create();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slugs = [];
        $skipped = [];

        for ($i = 1; $i < 101; ++$i) {
            $name = $this->faker->company();
            $slug = $this->generateSlug->make($name);
            if (in_array($slug, $slugs, strict: true)) {
                $skipped[] = $slug;
                continue;
            }

            if ($this->repository->count(['slug' => $slug]) >= 1) {
                $skipped[] = $slug;

                continue;
            }
            $slugs[] = $slug;

            $park = $this->factory->create($name);
            $this->entityManager->persist($park);

            if ($i % 5 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success('Aantal slugs zijn toegevoegd: ' . count($slugs) . PHP_EOL . 'Aantal slugs die zijn overgeslagen: ' . count($skipped));

        return Command::SUCCESS;
    }
}
