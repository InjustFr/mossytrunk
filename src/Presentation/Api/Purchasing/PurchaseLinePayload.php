<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\PurchaseLine;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PurchaseLinePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'product.required')]
        #[Assert\Ulid(message: 'product.invalid')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
        #[Assert\PositiveOrZero(message: 'pricePaid.negative')]
        public int $totalPrice = 0,
        #[Assert\PositiveOrZero(message: 'supplierOrder.received.negative')]
        public ?int $received = null,
    ) {
    }

    public function toLine(): PurchaseLine
    {
        return new PurchaseLine($this->productId, $this->variant, $this->quantity, $this->totalPrice, $this->received);
    }
}
