<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\UpdateDesign\UpdateDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}', name: 'api_designs_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateDesignController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] DesignPayload $payload, UpdateDesignHandler $updateDesign): Response
    {
        $updateDesign($payload->toUpdate($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
