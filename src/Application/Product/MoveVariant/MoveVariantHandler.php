<?php

declare(strict_types=1);

namespace App\Application\Product\MoveVariant;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class MoveVariantHandler
{
    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private DiscountRuleRepository $discountRules,
        private ProductReferenceGenerator $references,
        private Transaction $transaction,
        private WorkspaceContext $workspace,
    ) {
    }

    public function __invoke(MoveVariant $command): Ulid
    {
        $source = $this->products->get(Ulid::fromString($command->productId));
        $variant = self::blankToNull($command->variant);
        $source->sellable($variant);

        $target = null === $command->targetProductId ? $this->newProductLike($source, (string) $command->newProductName) : $this->products->get(Ulid::fromString($command->targetProductId));
        if ($target === $source) {
            throw InvalidProduct::movedOntoItself();
        }

        $targetVariant = self::blankToNull($command->targetVariant);
        if (null !== $targetVariant && !$target->hasVariant($targetVariant)) {
            if (!$target->hasVariants() && [] !== $this->orders->selling($target->id())) {
                throw InvalidProduct::soldWithoutVariant($target->displayName());
            }
            $target->addVariant($targetVariant);
        }
        $to = $target->sellable($targetVariant);

        foreach ($this->orders->selling($source->id()) as $order) {
            $order->moveSales($source->id(), $variant, $to);
        }

        if (null !== $variant) {
            $source->removeVariant($variant);
        }
        if (null === $variant || !$source->hasVariants()) {
            foreach ($this->discountRules->all() as $rule) {
                $rule->replaceEligibleProduct($source, $target);
            }
            $this->products->remove($source);
        }

        $this->transaction->commit();

        return $target->id();
    }

    private function newProductLike(Product $source, string $name): Product
    {
        $product = Product::create(
            $this->workspace->current(),
            $this->references->generate($source->type(), trim($name)),
            $name,
            $source->sellingPrice(),
            $source->buyingPrice(),
            [],
            $source->type(),
        );
        $this->products->add($product);

        return $product;
    }

    private static function blankToNull(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : trim($value);
    }
}
