<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Purchasing\SupplierOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSupplierOrderRepository implements SupplierOrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(SupplierOrder $order): void
    {
        $this->entityManager->persist($order);
    }

    public function remove(SupplierOrder $order): void
    {
        $this->entityManager->remove($order);
    }

    public function get(Ulid $id): SupplierOrder
    {
        return $this->scope->get(SupplierOrder::class, $id, 'supplier_order');
    }

    public function all(): array
    {
        return $this->scope->restrict($this->entityManager->createQueryBuilder()->select('o', 's', 'l')->from(SupplierOrder::class, 'o'), 'o')
            ->join('o.supplier', 's')
            ->leftJoin('o.lines', 'l')
            ->orderBy('o.orderedOn', 'DESC')
            ->addOrderBy('o.reference', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
