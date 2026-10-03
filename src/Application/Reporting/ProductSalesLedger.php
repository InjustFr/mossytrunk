<?php

declare(strict_types=1);

namespace App\Application\Reporting;

use App\Domain\Reporting\ProductSales;
use App\Domain\Shared\DateRange;
use Symfony\Component\Uid\Ulid;

/**
 * Units sold per product by the orders still counting as sales (not refunded), after their recorded share of the discounts.
 */
interface ProductSalesLedger
{
    /**
     * @return list<ProductSales> one per (product, variant), unidentified products included
     */
    public function ofEvent(Ulid $eventId): array;

    /**
     * @return list<ProductSales> one per identified product, all variants together
     */
    public function within(DateRange $period): array;

    public function ofProduct(Ulid $productId, ?DateRange $period = null): ?ProductSales;
}
