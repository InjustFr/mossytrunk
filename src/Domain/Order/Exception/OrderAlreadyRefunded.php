<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class OrderAlreadyRefunded extends InvalidOrder
{
    public function __construct(string $reference)
    {
        parent::__construct('order.already_refunded', ['reference' => $reference]);
    }
}
