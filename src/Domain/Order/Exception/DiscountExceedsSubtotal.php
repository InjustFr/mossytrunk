<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class DiscountExceedsSubtotal extends InvalidOrder
{
    public function __construct()
    {
        parent::__construct('order.discount_exceeds_subtotal');
    }
}
