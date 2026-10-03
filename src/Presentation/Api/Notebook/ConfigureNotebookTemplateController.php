<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\ConfigureNotebookTemplate\ConfigureNotebookTemplateHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notebook-template', name: 'api_notebook_template_configure', methods: ['PUT'], format: 'json')]
final class ConfigureNotebookTemplateController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] NotebookTemplatePayload $payload, ConfigureNotebookTemplateHandler $configure): Response
    {
        $configure($payload->toCommand());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
