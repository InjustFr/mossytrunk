<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\Restock\Restock;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RestockPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'product.required')]
        #[Assert\Ulid(message: 'product.invalid')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
        #[Assert\PositiveOrZero(message: 'pricePaid.negative')]
        public int $totalPaid = 0,
    ) {
    }

    public function toCommand(): Restock
    {
        return new Restock($this->productId, $this->variant, $this->quantity, $this->totalPaid);
    }
}
