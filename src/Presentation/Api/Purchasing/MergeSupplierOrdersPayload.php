<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MergeSupplierOrdersPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'supplierOrder.required')]
        #[Assert\Ulid(message: 'supplierOrder.required')]
        public string $orderId = '',
    ) {
    }
}
