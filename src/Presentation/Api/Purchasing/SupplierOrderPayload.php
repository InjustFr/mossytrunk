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
        #[Assert\NotBlank(message: 'Choisissez un fournisseur.')]
        #[Assert\Ulid(message: 'Fournisseur invalide.')]
        public string $supplierId = '',
        #[Assert\NotBlank(message: 'La date est obligatoire.')]
        #[Assert\Date(message: 'Date invalide.')]
        public string $orderedOn = '',
        #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un produit.')]
        #[Assert\Valid]
        public array $lines = [],
        #[Assert\PositiveOrZero(message: 'La remise globale ne peut pas être négative.')]
        public int $discount = 0,
        #[Assert\PositiveOrZero(message: 'Les frais de livraison ne peuvent pas être négatifs.')]
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
