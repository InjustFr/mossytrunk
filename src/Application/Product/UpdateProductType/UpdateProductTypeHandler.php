<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProductType;

use App\Application\Transaction;
use App\Domain\Product\Exception\TypeAlreadyExists;
use App\Domain\Product\Exception\TypeCodeAlreadyUsed;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId, string $name, string $color, ?string $code = null): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $sameName = $this->types->findByName($name);
        if (null !== $sameName && !$sameName->id()->equals($type->id())) {
            throw new TypeAlreadyExists(trim($name));
        }

        $type->rename($name);
        $type->recolor($color);
        if (null !== $code) {
            $this->recode($type, $code);
        }
        $this->transaction->commit();
    }

    private function recode(ProductType $type, string $code): void
    {
        $code = strtoupper(trim($code));
        if ($code !== $type->code() && $this->types->codeExists($code)) {
            throw new TypeCodeAlreadyUsed($code);
        }

        $type->recode($code);
    }
}
