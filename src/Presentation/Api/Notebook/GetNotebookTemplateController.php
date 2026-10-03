<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\GetNotebookTemplate\GetNotebookTemplateHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notebook-template', name: 'api_notebook_template', methods: ['GET'], format: 'json')]
final class GetNotebookTemplateController extends AbstractController
{
    public function __invoke(GetNotebookTemplateHandler $getTemplate): JsonResponse
    {
        return $this->json($getTemplate());
    }
}
