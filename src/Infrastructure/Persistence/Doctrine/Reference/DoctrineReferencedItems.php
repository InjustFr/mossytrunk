<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reference;

use App\Application\WorkspaceContext;
use App\Domain\Reference\Referenced;
use App\Domain\Reference\ReferencedItems;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;

abstract readonly class DoctrineReferencedItems implements ReferencedItems
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
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
        return $this->entityManager->createQueryBuilder()
            ->select('i')
            ->from($this->entity(), 'i')
            ->where('i.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME);
    }
}
