<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Shared\DateRange;

final class NoEventOnOrderDate extends InvalidOrder
{
    public function __construct(\DateTimeImmutable $placedAt)
    {
        parent::__construct(\sprintf(
            'Aucun événement le %s. Créez d\'abord l\'événement correspondant.',
            $placedAt->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('d/m/Y'),
        ));
    }
}
