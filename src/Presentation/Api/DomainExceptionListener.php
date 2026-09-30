<?php

declare(strict_types=1);

namespace App\Presentation\Api;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\Exception\NotFound;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Turns business rule violations into problem+json responses the UI can display as-is.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $status = $exception instanceof NotFound ? Response::HTTP_NOT_FOUND : Response::HTTP_UNPROCESSABLE_ENTITY;

        $event->setResponse(new JsonResponse(
            ['title' => 'Règle métier non respectée', 'status' => $status, 'detail' => $exception->getMessage()],
            $status,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
