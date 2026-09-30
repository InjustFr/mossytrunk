<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDesignRepository implements DesignRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(Design $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(Design $design): void
    {
        $this->entityManager->remove($design);
    }

    public function get(Ulid $id): Design
    {
        $result = $this->designs()
            ->andWhere('d.id = :id')
            ->setParameter('id', $id, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Design ? $result : throw new NotFound('Design', (string) $id);
    }

    public function findByProduct(Ulid $productId): ?Design
    {
        $design = $this->entityManager->createQueryBuilder()
            ->select('d')
            ->from(Design::class, 'd')
            ->join('d.declinations', 'x')
            ->where('d.workspace = :workspace')
            ->andWhere('x.productId = :product')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('product', $productId, UlidType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $design instanceof Design ? $design : null;
    }

    public function inCollection(Ulid $collectionId): array
    {
        return $this->designs()
            ->andWhere('c.id = :collection')
            ->setParameter('collection', $collectionId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function all(): array
    {
        return $this->designs()->getQuery()->getResult();
    }

    private function designs(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('d', 'c', 'x', 'g', 't')
            ->from(Design::class, 'd')
            ->leftJoin('d.collection', 'c')
            ->leftJoin('d.declinations', 'x')
            ->leftJoin('x.gabarit', 'g')
            ->leftJoin('g.type', 't')
            ->where('d.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('d.createdAt', 'DESC');
    }
}
