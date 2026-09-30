<?php

declare(strict_types=1);

namespace App\Application\Product\RestoreProductType;

use App\Application\Transaction;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RestoreProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId): void
    {
        $this->types->get(Ulid::fromString($typeId))->restore();
        $this->transaction->commit();
    }
}
