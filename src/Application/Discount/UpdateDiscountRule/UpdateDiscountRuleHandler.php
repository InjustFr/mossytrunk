<?php

declare(strict_types=1);

namespace App\Application\Discount\UpdateDiscountRule;

use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\EligibleProducts;
use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Changing a rule never alters past orders: they keep their discount snapshot.
 */
final readonly class UpdateDiscountRuleHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private EligibleProducts $eligibleProducts,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $ruleId, DiscountRuleDefinition $definition): void
    {
        $this->rules->get(Ulid::fromString($ruleId))->redefine(
            $definition->name,
            $this->eligibleProducts->resolve($definition->productIds),
            $definition->bundleSize,
            Money::cents($definition->bundlePriceCents),
            $this->eligibleProducts->resolveTypes($definition->typeIds),
        );

        $this->transaction->commit();
    }
}
