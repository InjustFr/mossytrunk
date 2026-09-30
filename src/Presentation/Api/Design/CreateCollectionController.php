<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\SaveCollection\SaveCollectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/design-collections', name: 'api_design_collections_create', methods: ['POST'], format: 'json')]
final class CreateCollectionController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] CollectionPayload $payload, SaveCollectionHandler $saveCollection): JsonResponse
    {
        return $this->json(['id' => (string) $saveCollection(null, $payload->name, $payload->description)], Response::HTTP_CREATED);
    }
}
