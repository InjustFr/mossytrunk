<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Discount\BasketLine;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Order\Exception\InvalidOrderQuantity;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

/**
 * Turns requested lines into validated items and computes the automatic bundle discounts.
 * Shared by placing and previewing an order so both always agree.
 */
final readonly class OrderPricing
{
    public function __construct(
        private ProductRepository $products,
        private DiscountRuleRepository $discountRules,
        private DiscountCalculator $calculator,
    ) {
    }

    /**
     * @param list<RequestedLine> $lines
     *
     * @return list<OrderedItem>
     */
    public function items(array $lines, \DateTimeImmutable $placedAt): array
    {
        return array_map(function (RequestedLine $line) use ($placedAt): OrderedItem {
            if ($line->quantity < 1) {
                throw new InvalidOrderQuantity();
            }

            $product = $this->products->get(Ulid::fromString($line->productId));

            return new OrderedItem($product->sellableOn(null, $line->variant, $placedAt), $line->quantity);
        }, $lines);
    }

    /**
     * @param list<OrderedItem> $items
     *
     * @return list<AppliedDiscount>
     */
    public function discounts(array $items, \DateTimeImmutable $placedAt): array
    {
        $basket = array_values(array_filter(array_map(static fn (OrderedItem $ordered): ?BasketLine => BasketLine::of($ordered->item, $ordered->quantity), $items)));

        return $this->calculator->calculate($basket, $this->discountRules->all(), $placedAt);
    }
}
