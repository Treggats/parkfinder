<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ParkPayload;
use App\Entity\Park;
use App\Factory\ParkFactory;
use App\Repository\ParkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ParkController extends AbstractController
{
    public function __construct(
        private readonly ParkRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParkFactory $parkFactory,
    ) {
    }

    #[Route('/parks', name: 'park_index', methods: ['GET'])]
    public function index(
        #[MapQueryParameter('page', FILTER_VALIDATE_INT, options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(
            name: 'perPage',
            filter: FILTER_VALIDATE_INT,
            options: ['min_range' => 1, 'max_range' => 150],
            validationFailedStatusCode: Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE
        )] int $perPage = 25,
    ): Response {
        $paginator = $this->repository->paginate(
            page: $page,
            perPage: $perPage,
        );

        return $this->render('park/index.html.twig', [
            'paginator' => $paginator,
        ]);
    }

    #[Route('/parks/{slug:park}', name: 'park_show', methods: ['GET'])]
    public function show(Park $park): Response
    {
        return $this->render('park/show.html.twig', [
            'park' => $park,
        ]);
    }

    #[Route('/parks', name: 'park_store', methods: ['POST'])]
    public function store(#[MapRequestPayload] ParkPayload $payload): Response
    {
        $park = $this->parkFactory->create(
            name: $payload->name,
            hasPool: $payload->hasPool,
        );

        $this->entityManager->persist($park);
        $this->entityManager->flush();

        return $this->json($park, status: Response::HTTP_CREATED, headers: [
            'Location' => $this->generateUrl('park_show', ['slug' => $park->getSlug()]),
        ]);
    }
}
