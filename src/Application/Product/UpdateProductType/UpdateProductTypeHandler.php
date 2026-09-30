<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProductType;

use App\Application\Transaction;
use App\Domain\Product\Exception\TypeAlreadyExists;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId, string $name, string $color): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $sameName = $this->types->findByName($name);
        if (null !== $sameName && !$sameName->id()->equals($type->id())) {
            throw new TypeAlreadyExists(trim($name));
        }

        $type->rename($name);
        $type->recolor($color);
        $this->transaction->commit();
    }
}
