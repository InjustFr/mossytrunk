<?php

declare(strict_types=1);

namespace App\Application\Discount\DeleteDiscountRule;

use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteDiscountRuleHandler
{
    public function __construct(
        private DiscountRuleRepository $rules,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $ruleId): void
    {
        $this->rules->remove($this->rules->get(Ulid::fromString($ruleId)));
        $this->transaction->commit();
    }
}
