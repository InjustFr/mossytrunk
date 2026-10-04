<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteProductType;

use App\Application\Transaction;
use App\Domain\Design\GabaritRepository;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\Exception\TypeStillUsed;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductRepository $products,
        private GabaritRepository $gabarits,
        private DiscountRuleRepository $discountRules,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $products = \count($this->products->ofType($type));
        $gabarits = \count($this->gabarits->ofType($type));
        if ($products > 0 || $gabarits > 0) {
            throw new TypeStillUsed($type->name(), $products, $gabarits);
        }

        foreach ($this->discountRules->all() as $rule) {
            $rule->withdrawType($type);
        }
        $this->types->remove($type);
        $this->transaction->commit();
    }
}
