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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns business rule violations into problem+json responses the UI can display as-is.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class DomainExceptionListener
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $status = $exception instanceof NotFound ? Response::HTTP_NOT_FOUND : Response::HTTP_UNPROCESSABLE_ENTITY;

        $event->setResponse(new JsonResponse(
            [
                'title' => $this->translator->trans('problem.business_rule'),
                'status' => $status,
                'detail' => $this->translator->trans($exception->getMessage(), $exception->parameters(), 'exceptions'),
            ],
            $status,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
