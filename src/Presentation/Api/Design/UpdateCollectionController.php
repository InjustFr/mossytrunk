<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\UpdateCollection\UpdateCollectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/design-collections/{id}', name: 'api_design_collections_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateCollectionController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] CollectionPayload $payload, UpdateCollectionHandler $updateCollection): Response
    {
        $updateCollection($id, $payload->name, $payload->description);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
