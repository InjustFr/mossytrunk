<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Order\Order;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class SalesByProduct
{
    /**
     * @param array<string, ProductSales> $products keyed by product id
     */
    private function __construct(private array $products)
    {
    }

    /**
     * @param list<Order> $orders
     */
    public static function of(array $orders): self
    {
        $products = [];
        foreach ($orders as $order) {
            foreach ($order->lines() as $line) {
                $key = (string) $line->productId();
                $products[$key] = ($products[$key] ?? new ProductSales($line->productName(), $line->productId(), $line->productName(), null, 0, Money::zero(), Money::zero(), false))
                    ->add($line->quantity(), $line->total(), $line->cost(), $line->unitCost()->isZero());
            }
        }

        return new self($products);
    }

    public function forProduct(Ulid $productId): ?ProductSales
    {
        return $this->products[(string) $productId] ?? null;
    }

    /**
     * @return list<ProductSales> best sales first
     */
    public function ranked(): array
    {
        $ranked = array_values($this->products);
        usort($ranked, static fn (ProductSales $a, ProductSales $b): int => [$b->sales->amount(), $a->label] <=> [$a->sales->amount(), $b->label]);

        return $ranked;
    }
}
