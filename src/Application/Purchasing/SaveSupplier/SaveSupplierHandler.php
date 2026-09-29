<?php

declare(strict_types=1);

namespace App\Application\Purchasing\SaveSupplier;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Purchasing\InvalidPurchase;
use App\Domain\Purchasing\Supplier;
use App\Domain\Purchasing\SupplierRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SaveSupplierHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(SaveSupplier $command): Supplier
    {
        $namesake = $this->suppliers->findByName($command->name);

        if (null === $command->supplierId) {
            if (null !== $namesake) {
                throw InvalidPurchase::supplierAlreadyExists($namesake->name());
            }
            $supplier = Supplier::create($this->workspace->current(), $command->name, $command->contact, $command->notes);
            $this->suppliers->add($supplier);
        } else {
            $supplier = $this->suppliers->get(Ulid::fromString($command->supplierId));
            if (null !== $namesake && $namesake !== $supplier) {
                throw InvalidPurchase::supplierAlreadyExists($namesake->name());
            }
            $supplier->describe($command->name, $command->contact, $command->notes);
        }

        $this->transaction->commit();

        return $supplier;
    }
}
