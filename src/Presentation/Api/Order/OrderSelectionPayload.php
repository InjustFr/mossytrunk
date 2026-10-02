<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderSelectionPayload
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'orders.selectAtLeastOne')]
        #[Assert\All([new Assert\Ulid()])]
        public array $orderIds = [],
    ) {
    }
}
