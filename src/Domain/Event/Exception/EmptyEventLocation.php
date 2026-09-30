<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class EmptyEventLocation extends InvalidEvent
{
    public function __construct()
    {
        parent::__construct('Le lieu de l\'événement est obligatoire.');
    }
}
