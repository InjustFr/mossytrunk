<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use App\Domain\Stock\StockItem;

final readonly class StockPotential
{
    private function __construct(
        public int $units,
        public Money $turnover,
        public Money $stockCost,
    ) {
    }

    public static function none(): self
    {
        return new self(0, Money::zero(), Money::zero());
    }

    /**
     * @param list<StockItem> $stockItems
     */
    public static function of(Product $product, array $stockItems): self
    {
        if ($product->isSupply()) {
            return self::none();
        }

        $potential = self::none();
        foreach ($stockItems as $item) {
            if ($item->onHand() > 0 && $product->sells($item->variant())) {
                $potential = $potential->add(new self($item->onHand(), $product->sellingPrice()->multiply($item->onHand()), $item->remainingValue()));
            }
        }

        return $potential;
    }

    public function add(self $other): self
    {
        return new self($this->units + $other->units, $this->turnover->add($other->turnover), $this->stockCost->add($other->stockCost));
    }

    public function urssaf(): Money
    {
        return UrssafContribution::on($this->turnover);
    }

    public function revenue(): Money
    {
        return $this->turnover->subtract($this->urssaf())->subtract($this->stockCost);
    }

    /**
     * @return array{units: int, turnover: int, stockCost: int, urssaf: int, revenue: int}
     */
    public function toArray(): array
    {
        return [
            'units' => $this->units,
            'turnover' => $this->turnover->amount(),
            'stockCost' => $this->stockCost->amount(),
            'urssaf' => $this->urssaf()->amount(),
            'revenue' => $this->revenue()->amount(),
        ];
    }
}
