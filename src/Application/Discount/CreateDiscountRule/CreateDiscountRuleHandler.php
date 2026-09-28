<?php

declare(strict_types=1);

namespace App\Application\Discount\CreateDiscountRule;

use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\EligibleProducts;
use App\Application\Transaction;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class CreateDiscountRuleHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private EligibleProducts $eligibleProducts,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(DiscountRuleDefinition $definition): Ulid
    {
        $rule = DiscountRule::create(
            $definition->name,
            $this->eligibleProducts->resolve($definition->productIds),
            $definition->bundleSize,
            Money::cents($definition->bundlePriceCents),
            $this->eligibleProducts->resolveTypes($definition->typeIds),
        );

        $this->rules->add($rule);
        $this->transaction->commit();

        return $rule->id();
    }
}
