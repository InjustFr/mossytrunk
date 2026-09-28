<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Transaction;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

/**
 * Creates a product type; its code is derived from the name and made unique (PRI, PRI2…).
 */
final readonly class CreateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $name): ProductType
    {
        $type = $this->create($name);
        $this->transaction->commit();

        return $type;
    }

    /**
     * Creates without committing, for use inside another use case (e.g. SumUp import).
     */
    public function create(string $name): ProductType
    {
        if (null !== $this->types->findByName($name)) {
            throw InvalidProduct::typeAlreadyExists(trim($name));
        }

        $base = ProductType::codeFor($name);
        $code = $base;
        for ($i = 2; $this->types->codeExists($code); ++$i) {
            $code = $base.$i;
        }

        $type = ProductType::create($name, $code);
        $this->types->add($type);

        return $type;
    }
}
