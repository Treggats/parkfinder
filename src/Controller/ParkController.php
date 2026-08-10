<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Park;
use App\Repository\ParkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParkController extends AbstractController
{
    public function __construct(
        private readonly ParkRepository $repository,
    ) {
    }

    #[Route('/parks', name: 'park_index', methods: ['GET'])]
    public function index(): Response
    {
        $parks = $this->repository->findBy([], limit: 25);

        return $this->render('park/index.html.twig', [
            'parks' => $parks,
        ]);
    }

    #[Route('/parks/{slug:park}', name: 'park_show', methods: ['GET'])]
    public function show(Park $park): Response
    {
        return $this->render('park/show.html.twig', [
            'park' => $park,
        ]);
    }
}
