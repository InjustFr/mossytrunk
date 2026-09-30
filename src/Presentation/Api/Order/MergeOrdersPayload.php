<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MergeOrdersPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'order.required')]
        #[Assert\Ulid(message: 'order.required')]
        public string $orderId = '',
    ) {
    }
}
