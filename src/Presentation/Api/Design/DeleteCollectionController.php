<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\DeleteCollection\DeleteCollectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/design-collections/{id}', name: 'api_design_collections_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteCollectionController extends AbstractController
{
    public function __invoke(string $id, DeleteCollectionHandler $deleteCollection): Response
    {
        $deleteCollection($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
