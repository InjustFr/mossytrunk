<?php

declare(strict_types=1);

namespace App\Presentation\Api;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class SameOriginGuard
{
    private const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()
            || !str_starts_with($request->getPathInfo(), '/api/')
            || \in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return;
        }

        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        $origin = $request->headers->get('Origin');

        $sameOrigin = null !== $fetchSite
            ? \in_array($fetchSite, ['same-origin', 'none'], true)
            : null === $origin || $origin === $request->getSchemeAndHttpHost();

        if (!$sameOrigin) {
            $event->setResponse(new JsonResponse(
                ['title' => 'Requête refusée', 'status' => Response::HTTP_FORBIDDEN, 'detail' => 'Requête d\'une autre origine refusée.'],
                Response::HTTP_FORBIDDEN,
                ['Content-Type' => 'application/problem+json'],
            ));
        }
    }
}
