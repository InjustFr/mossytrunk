<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Shared\DateRange;

final class OrderOutsideEvent extends InvalidOrder
{
    public function __construct(string $eventName, \DateTimeImmutable $placedAt)
    {
        parent::__construct(\sprintf(
            'La date de la commande (%s) doit être comprise dans les dates de l\'événement « %s ».',
            $placedAt->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('d/m/Y H:i'),
            $eventName,
        ));
    }
}
