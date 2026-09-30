<?php

declare(strict_types=1);

namespace App\Application\Product\ForgetSellingPrice;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ForgetSellingPriceHandler
{
    public function __construct(
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, string $changeId): void
    {
        $this->products->get(Ulid::fromString($productId))->forgetPrice(Ulid::fromString($changeId));
        $this->transaction->commit();
    }
}
