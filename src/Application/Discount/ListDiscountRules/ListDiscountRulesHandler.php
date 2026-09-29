<?php

declare(strict_types=1);

namespace App\Application\Discount\ListDiscountRules;

use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use Psr\Clock\ClockInterface;

final readonly class ListDiscountRulesHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<DiscountRuleView>
     */
    public function __invoke(): array
    {
        $today = $this->clock->now();

        return array_map(static fn (DiscountRule $rule): DiscountRuleView => DiscountRuleView::fromRule($rule, $today), $this->rules->all());
    }
}
