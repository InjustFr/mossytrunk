<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reference;

use App\Domain\Reference\Referenced;
use App\Domain\Reference\ReferencedItems;
use App\Infrastructure\Persistence\Doctrine\WorkspaceScope;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

abstract readonly class DoctrineReferencedItems implements ReferencedItems
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    /**
     * @return class-string<Referenced>
     */
    abstract protected function entity(): string;

    abstract protected function momentField(): string;

    public function holds(string $reference): bool
    {
        return null !== $this->items()
            ->select('i.id')
            ->andWhere('i.reference = :reference')
            ->setParameter('reference', $reference)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function count(): int
    {
        return (int) $this->items()->select('COUNT(i.id)')->getQuery()->getSingleScalarResult();
    }

    public function oldestFirst(): array
    {
        $items = $this->items()->orderBy('i.'.$this->momentField(), 'ASC')->addOrderBy('i.id', 'ASC')->getQuery()->toIterable();

        return array_values(array_filter(iterator_to_array($items, false), static fn (mixed $item): bool => $item instanceof Referenced));
    }

    private function items(): QueryBuilder
    {
        return $this->scope->restrict($this->entityManager->createQueryBuilder()->select('i')->from($this->entity(), 'i'), 'i');
    }
}
