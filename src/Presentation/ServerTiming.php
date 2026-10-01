<?php

declare(strict_types=1);

namespace App\Presentation;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ServerTiming
{
    private const string STARTED_AT = '_server_timing_started_at';

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 4096)]
    public function start(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getRequest()->attributes->set(self::STARTED_AT, hrtime(true));
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -4096)]
    public function report(ResponseEvent $event): void
    {
        $startedAt = $event->getRequest()->attributes->get(self::STARTED_AT);
        if ($event->isMainRequest() && \is_int($startedAt)) {
            $event->getResponse()->headers->set('Server-Timing', \sprintf('app;dur=%.1f', (hrtime(true) - $startedAt) / 1_000_000));
        }
    }
}
