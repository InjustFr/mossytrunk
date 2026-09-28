<?php

declare(strict_types=1);

namespace App\Application\Discount\ListDiscountRules;

use App\Domain\Discount\DiscountRuleRepository;

final readonly class ListDiscountRulesHandler
{
    public function __construct(private DiscountRuleRepository $rules)
    {
    }

    /**
     * @return list<DiscountRuleView>
     */
    public function __invoke(): array
    {
        return array_map(DiscountRuleView::fromRule(...), $this->rules->all());
    }
}
