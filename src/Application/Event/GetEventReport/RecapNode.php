<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Reporting\ProductSales;

final class RecapNode
{
    public int $quantity = 0;
    public int $sales = 0;
    public int $cost = 0;
    public bool $unknownCost = false;

    /** @var array<string, RecapNode> */
    private array $children = [];

    public function __construct(
        public readonly string $label,
    ) {
    }

    public function child(string $key, string $label): self
    {
        return $this->children[$key] ??= new self($label);
    }

    public function add(ProductSales $line): void
    {
        $this->quantity += $line->quantity;
        $this->sales += $line->sales->amount();
        $this->cost += $line->cost->amount();
        $this->unknownCost = $this->unknownCost || $line->unknownCost;
    }

    /**
     * @return list<RecapNode>
     */
    public function children(): array
    {
        return self::sorted(array_values($this->children));
    }

    /**
     * @param list<RecapNode> $nodes
     *
     * @return list<RecapNode>
     */
    public static function sorted(array $nodes): array
    {
        usort($nodes, static fn (self $a, self $b): int => [$b->sales, $a->label] <=> [$a->sales, $b->label]);

        return $nodes;
    }

    /**
     * @return array{quantity: int, sales: int, cost: int, unknownCost: bool}
     */
    public function figures(): array
    {
        return ['quantity' => $this->quantity, 'sales' => $this->sales, 'cost' => $this->cost, 'unknownCost' => $this->unknownCost];
    }
}
