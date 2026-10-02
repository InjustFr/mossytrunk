<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\PurchaseLine;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Domain\Purchasing\Currency;
use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Shared\DateRange;
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
        #[Assert\Length(max: 100)]
        public ?string $supplierReference = null,
        #[Assert\Date(message: 'date.invalid')]
        public ?string $receivedOn = null,
        #[Assert\Choice(choices: ['EUR', 'USD'], message: 'supplierOrder.currency.invalid')]
        public string $currency = 'EUR',
        #[Assert\Positive(message: 'supplierOrder.exchangeRate.positive')]
        public float $exchangeRate = 1.0,
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
            $this->supplierReference,
            null === $this->receivedOn || '' === $this->receivedOn ? null : new \DateTimeImmutable($this->receivedOn.' 12:00', new \DateTimeZone(DateRange::TIMEZONE)),
            Currency::from($this->currency),
            (int) round($this->exchangeRate * SupplierOrder::EURO_RATE),
        );
    }
}
