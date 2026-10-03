<?php

declare(strict_types=1);

namespace App\Presentation\Api\Identity;

use App\Application\Identity\ChooseTheme\ChooseThemeHandler;
use App\Domain\Identity\Theme;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me/theme', name: 'api_me_theme', methods: ['PUT'], format: 'json')]
final class ChooseThemeController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] ThemePayload $payload, ChooseThemeHandler $chooseTheme): Response
    {
        $chooseTheme(Theme::of($payload->background, $payload->accent));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
