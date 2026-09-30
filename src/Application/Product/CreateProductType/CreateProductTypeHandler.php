<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

/**
 * Creates a product type; its code is derived from the name and made unique (PRI, PRI2…).
 * Without a chosen colour, it takes the next one of the palette.
 */
final readonly class CreateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private Transaction $transaction,
        private WorkspaceContext $workspace,
    ) {
    }

    public function __invoke(string $name, ?string $color = null): ProductType
    {
        $type = $this->create($name, $color);
        $this->transaction->commit();

        return $type;
    }

    /**
     * Creates without committing, for use inside another use case (e.g. SumUp import).
     */
    public function create(string $name, ?string $color = null): ProductType
    {
        if (null !== $this->types->findByName($name)) {
            throw InvalidProduct::typeAlreadyExists(trim($name));
        }

        $base = ProductType::codeFor($name);
        $code = $base;
        for ($i = 2; $this->types->codeExists($code); ++$i) {
            $code = $base.$i;
        }

        $color ??= ProductType::paletteColor(\count($this->types->all()));
        $type = ProductType::create($this->workspace->current(), $name, $code, $color);
        $this->types->add($type);

        return $type;
    }
}
