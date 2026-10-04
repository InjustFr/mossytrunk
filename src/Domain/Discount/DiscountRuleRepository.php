<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use Symfony\Component\Uid\Ulid;

interface DiscountRuleRepository
{
    public function add(DiscountRule $rule): void;

    public function remove(DiscountRule $rule): void;

    public function get(Ulid $id): DiscountRule;

    /**
     * @return list<DiscountRule>
     */
    public function all(): array;
}
