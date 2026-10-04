<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Design\Design;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use App\Infrastructure\Persistence\Doctrine\Reporting\SqlValue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDesignCollectionRepository implements DesignCollectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(DesignCollection $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(DesignCollection $collection): void
    {
        $this->entityManager->remove($collection);
    }

    public function get(Ulid $id): DesignCollection
    {
        return $this->scope->get(DesignCollection::class, $id, 'collection');
    }

    public function all(): array
    {
        return $this->scope->findBy(DesignCollection::class, orderBy: ['name' => 'ASC']);
    }

    public function byProduct(): array
    {
        $collections = [];
        foreach ($this->all() as $collection) {
            $collections[$collection->id()->toRfc4122()] = $collection;
        }

        $rows = $this->scope->restrict($this->entityManager->createQueryBuilder()->select('x.productId AS productId', 'IDENTITY(d.collection) AS collectionId')->from(Design::class, 'd'), 'd')
            ->join('d.declinations', 'x')
            ->andWhere('d.collection IS NOT NULL')
            ->andWhere('x.productId IS NOT NULL')
            ->getQuery()
            ->getScalarResult();

        $byProduct = [];
        foreach ($rows as $row) {
            $productId = \is_array($row) ? $row['productId'] ?? null : null;
            $collection = $collections[SqlValue::string(\is_array($row) ? $row['collectionId'] ?? null : null)] ?? null;
            if (null !== $collection && ($productId instanceof Ulid || \is_string($productId))) {
                $byProduct[(string) Ulid::fromString((string) $productId)] = $collection;
            }
        }

        return $byProduct;
    }
}
