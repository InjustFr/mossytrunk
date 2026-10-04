<?php

declare(strict_types=1);

namespace App\Tests\Support\EndToEnd;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 4096)]
final readonly class KeepStaticConnections
{
    public function __construct(private bool $enabled)
    {
    }

    public function __invoke(): void
    {
        if ($this->enabled) {
            StaticDriver::setKeepStaticConnections(true);
        }
    }
}
