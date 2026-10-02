<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\AddOrderSupplies\AddOrderSupplies;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderSuppliesPayload
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'orders.selectAtLeastOne')]
        #[Assert\All([new Assert\Ulid()])]
        public array $orderIds = [],
        #[Assert\NotBlank(message: 'supply.required')]
        #[Assert\Ulid(message: 'supply.required')]
        public string $supplyId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
    ) {
    }

    public function toCommand(): AddOrderSupplies
    {
        return new AddOrderSupplies($this->orderIds, $this->supplyId, null === $this->variant || '' === trim($this->variant) ? null : $this->variant, $this->quantity);
    }
}
