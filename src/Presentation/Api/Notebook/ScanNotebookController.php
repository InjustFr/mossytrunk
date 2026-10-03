<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\Exception\InvalidNotebookPages;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\ScanNotebook\ScanNotebook;
use App\Application\Notebook\ScanNotebook\ScanNotebookHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/notebook', name: 'api_events_notebook_scan', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class ScanNotebookController extends AbstractController
{
    private const array IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __invoke(string $id, Request $request, ScanNotebookHandler $scanNotebook): JsonResponse
    {
        $pages = array_map(self::page(...), array_values(array_filter($request->files->all('pages'))));

        return $this->json(['id' => (string) $scanNotebook(new ScanNotebook($id, $pages))], Response::HTTP_CREATED);
    }

    private static function page(mixed $file): NotebookPage
    {
        if (!$file instanceof UploadedFile || !$file->isValid() || $file->getSize() > ScanNotebookHandler::MAX_PAGE_MEGABYTES * 1024 * 1024 || !\in_array($file->getMimeType(), self::IMAGE_TYPES, true)) {
            throw new InvalidNotebookPages(ScanNotebookHandler::MAX_PAGES, ScanNotebookHandler::MAX_PAGE_MEGABYTES);
        }

        return new NotebookPage($file->getClientOriginalName(), (string) $file->getMimeType(), $file->getContent());
    }
}
