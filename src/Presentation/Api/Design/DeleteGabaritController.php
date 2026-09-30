<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\DeleteGabarit\DeleteGabaritHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/gabarits/{id}', name: 'api_gabarits_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteGabaritController extends AbstractController
{
    public function __invoke(string $id, DeleteGabaritHandler $deleteGabarit): Response
    {
        $deleteGabarit($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
