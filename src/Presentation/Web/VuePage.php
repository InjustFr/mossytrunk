<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class VuePage
{
    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
        private ApiPreload $preload,
    ) {
    }

    /**
     * @param array<string, mixed> $props
     * @param list<string>         $preload
     */
    public function render(string $component, string $titleKey, array $props = [], int $status = Response::HTTP_OK, array $preload = []): Response
    {
        return new Response($this->twig->render('page.html.twig', [
            'component' => $component,
            'title' => $this->translator->trans($titleKey, domain: 'pages'),
            'props' => $props,
            'preloaded' => [] === $preload ? '{}' : $this->preload->json($preload),
        ]), $status);
    }
}
