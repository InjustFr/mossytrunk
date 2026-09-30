<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Transaction;
use App\Domain\Product\ProductType;

final readonly class CreateProductTypeHandler
{
    public function __construct(
        private ProductTypeCreator $creator,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $name, ?string $color = null): ProductType
    {
        $type = $this->creator->create($name, $color);
        $this->transaction->commit();

        return $type;
    }
}
