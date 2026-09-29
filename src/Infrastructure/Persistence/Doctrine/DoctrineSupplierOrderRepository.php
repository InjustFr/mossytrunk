<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Purchasing\SupplierOrderRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSupplierOrderRepository implements SupplierOrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
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
        return $this->entityManager->getRepository(SupplierOrder::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw NotFound::entity('Commande fournisseur', (string) $id);
    }

    public function all(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o', 's', 'l')
            ->from(SupplierOrder::class, 'o')
            ->join('o.supplier', 's')
            ->leftJoin('o.lines', 'l')
            ->where('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('o.orderedOn', 'DESC')
            ->addOrderBy('o.reference', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
