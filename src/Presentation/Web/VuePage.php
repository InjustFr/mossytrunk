<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class VuePage
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param array<string, mixed> $props
     */
    public function render(string $component, string $title, array $props = [], int $status = Response::HTTP_OK): Response
    {
        return new Response($this->twig->render('page.html.twig', [
            'component' => $component,
            'title' => $title,
            'props' => $props,
        ]), $status);
    }
}
