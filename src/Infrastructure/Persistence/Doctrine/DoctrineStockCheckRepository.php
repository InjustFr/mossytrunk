<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineStockCheckRepository implements StockCheckRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(StockCheck $check): void
    {
        $this->entityManager->persist($check);
    }

    public function get(Ulid $id): StockCheck
    {
        $result = $this->checks()
            ->andWhere('c.id = :id')
            ->setParameter('id', $id, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof StockCheck ? $result : throw new NotFound('stock_check', (string) $id);
    }

    public function ofEvent(Ulid $eventId): array
    {
        return $this->checks()
            ->andWhere('e.id = :event')
            ->setParameter('event', $eventId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function withUnexplainedUnits(): array
    {
        return $this->checks()
            ->andWhere('EXISTS (SELECT 1 FROM '.StockCheck::class.' u JOIN u.lines ul WHERE u = c AND ul.unexplained > 0 AND ul.supply = false)')
            ->getQuery()
            ->getResult();
    }

    public function consumingSupplies(): array
    {
        return $this->checks()
            ->andWhere('EXISTS (SELECT 1 FROM '.StockCheck::class.' s JOIN s.lines sl WHERE s = c AND sl.supply = true AND sl.unexplained > 0)')
            ->getQuery()
            ->getResult();
    }

    public function counting(Ulid $productId): array
    {
        return $this->checks()
            ->andWhere('EXISTS (SELECT 1 FROM '.StockCheck::class.' p JOIN p.lines pl WHERE p = c AND pl.productId = :product)')
            ->setParameter('product', $productId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    private function checks(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c', 'l', 'e')
            ->from(StockCheck::class, 'c')
            ->join('c.event', 'e')
            ->leftJoin('c.lines', 'l')
            ->where('c.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('c.checkedAt', 'DESC');
    }
}
