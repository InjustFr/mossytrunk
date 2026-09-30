<?php

declare(strict_types=1);

namespace App\Presentation\Api\Identity;

use App\Application\Identity\ChooseLanguage\ChooseLanguageHandler;
use App\Domain\Identity\Language;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me/language', name: 'api_me_language', methods: ['PUT'], format: 'json')]
final class ChooseLanguageController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] LanguagePayload $payload, ChooseLanguageHandler $chooseLanguage): Response
    {
        $chooseLanguage(Language::from($payload->language));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
