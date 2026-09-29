<?php

declare(strict_types=1);

namespace App\Application\Discount\ToggleDiscountRule;

use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ToggleDiscountRuleHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $ruleId, bool $running): void
    {
        $rule = $this->rules->get(Ulid::fromString($ruleId));
        $running ? $rule->startOn($this->clock->now()) : $rule->stopBefore($this->clock->now());

        $this->transaction->commit();
    }
}
