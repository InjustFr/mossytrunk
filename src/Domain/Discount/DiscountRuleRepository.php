<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use Symfony\Component\Uid\Ulid;

interface DiscountRuleRepository
{
    public function add(DiscountRule $rule): void;

    public function remove(DiscountRule $rule): void;

    /**
     * @throws \App\Domain\Shared\NotFound
     */
    public function get(Ulid $id): DiscountRule;

    /**
     * @return list<DiscountRule> sorted by name
     */
    public function all(): array;
}
