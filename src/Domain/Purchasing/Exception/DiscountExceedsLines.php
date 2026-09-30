<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class DiscountExceedsLines extends InvalidPurchase
{
    public function __construct()
    {
        parent::__construct('La remise globale ne peut pas dépasser le prix des produits.');
    }
}
