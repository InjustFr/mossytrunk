<?php

declare(strict_types=1);

namespace App\Application\Product\Variants;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Product\VariantLabel;
use Symfony\Component\Uid\Ulid;

final readonly class RenameTypeVariantHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductRepository $products,
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private DiscountRuleRepository $discountRules,
        private VariantRelabelling $relabelling,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId, string $from, string $to): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));
        $current = $type->renameVariant($from, $to);
        $to = VariantLabel::clean($to);

        $products = $this->products->ofType($type);
        foreach ($products as $product) {
            $product->renameVariant($current, $to);
        }
        foreach ($this->gabarits->all() as $gabarit) {
            if ($gabarit->type() === $type) {
                $gabarit->renameVariant($current, $to);
            }
        }
        foreach ($this->designs->all() as $design) {
            $design->renameVariant($type, $current, $to);
        }
        foreach ($this->discountRules->all() as $rule) {
            $rule->renameVariant($type, $current, $to);
        }

        $this->relabelling->relabel(array_map(static fn (Product $product): Ulid => $product->id(), $products), $current, $to);
        $this->transaction->commit();
    }
}
