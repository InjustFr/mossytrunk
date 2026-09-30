<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\DeleteDesign\DeleteDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}', name: 'api_designs_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteDesignController extends AbstractController
{
    public function __invoke(string $id, DeleteDesignHandler $deleteDesign): Response
    {
        $deleteDesign($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
