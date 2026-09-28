<?php

declare(strict_types=1);

namespace App\Application\Discount\ToggleDiscountRule;

use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ToggleDiscountRuleHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $ruleId, bool $active): void
    {
        $rule = $this->rules->get(Ulid::fromString($ruleId));
        $active ? $rule->activate() : $rule->deactivate();

        $this->transaction->commit();
    }
}
