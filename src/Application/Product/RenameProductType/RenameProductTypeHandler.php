<?php

declare(strict_types=1);

namespace App\Application\Product\RenameProductType;

use App\Application\Transaction;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

/**
 * Renaming a type changes how its products are displayed from now on; past orders keep their snapshot.
 */
final readonly class RenameProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId, string $name): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $sameName = $this->types->findByName($name);
        if (null !== $sameName && !$sameName->id()->equals($type->id())) {
            throw InvalidProduct::typeAlreadyExists(trim($name));
        }

        $type->rename($name);
        $this->transaction->commit();
    }
}
