<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\PurchaseLine;
use App\Application\Purchasing\SupplierOrderDraft;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupplierOrderPayload
{
    /**
     * @param list<PurchaseLinePayload> $lines
     */
    public function __construct(
        #[Assert\NotBlank(message: 'supplier.required')]
        #[Assert\Ulid(message: 'supplier.invalid')]
        public string $supplierId = '',
        #[Assert\NotBlank(message: 'date.required')]
        #[Assert\Date(message: 'date.invalid')]
        public string $orderedOn = '',
        #[Assert\Count(min: 1, minMessage: 'products.atLeastOne')]
        #[Assert\Valid]
        public array $lines = [],
        #[Assert\PositiveOrZero(message: 'supplierOrder.discount.negative')]
        public int $discount = 0,
        #[Assert\PositiveOrZero(message: 'supplierOrder.deliveryFees.negative')]
        public int $deliveryFees = 0,
    ) {
    }

    public function toDraft(): SupplierOrderDraft
    {
        return new SupplierOrderDraft(
            $this->supplierId,
            new \DateTimeImmutable($this->orderedOn),
            array_map(static fn (PurchaseLinePayload $line): PurchaseLine => $line->toLine(), $this->lines),
            $this->discount,
            $this->deliveryFees,
        );
    }
}
