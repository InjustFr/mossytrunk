<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\WorkOnCollection\WorkOnCollectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/design-collections/{id}/current', name: 'api_design_collections_current', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class WorkOnCollectionController extends AbstractController
{
    public function __invoke(string $id, Request $request, WorkOnCollectionHandler $workOn): Response
    {
        $workOn($id, (bool) $request->getPayload()->get('current'));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
