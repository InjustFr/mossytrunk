<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\Exception\InvalidNotebookPhoto;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\RecognizeNotebookPage\RecognizeNotebookPageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notebook/recognitions', name: 'api_notebook_recognize', methods: ['POST'], format: 'json')]
final class RecognizeNotebookPageController extends AbstractController
{
    private const array IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __invoke(Request $request, RecognizeNotebookPageHandler $recognize): JsonResponse
    {
        $photo = $request->files->get('photo');
        if (!$photo instanceof UploadedFile || !$photo->isValid() || $photo->getSize() > RecognizeNotebookPageHandler::MAX_PHOTO_MEGABYTES * 1024 * 1024 || !\in_array($photo->getMimeType(), self::IMAGE_TYPES, true)) {
            throw new InvalidNotebookPhoto(RecognizeNotebookPageHandler::MAX_PHOTO_MEGABYTES);
        }

        return $this->json(['text' => $recognize(new NotebookPage($photo->getClientOriginalName(), (string) $photo->getMimeType(), $photo->getContent()))]);
    }
}
