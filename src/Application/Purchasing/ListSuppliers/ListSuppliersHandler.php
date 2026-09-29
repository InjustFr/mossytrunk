<?php

declare(strict_types=1);

namespace App\Application\Purchasing\ListSuppliers;

use App\Application\Purchasing\SupplierView;
use App\Domain\Purchasing\SupplierRepository;

final readonly class ListSuppliersHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
    ) {
    }

    /**
     * @return list<SupplierView>
     */
    public function __invoke(): array
    {
        return array_map(SupplierView::of(...), $this->suppliers->all());
    }
}
