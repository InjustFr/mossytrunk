<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Shared\Exception\NotFound;
use App\Infrastructure\Persistence\Doctrine\Reporting\SqlValue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDesignCollectionRepository implements DesignCollectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
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
        return $this->entityManager->getRepository(DesignCollection::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('collection', (string) $id);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(DesignCollection::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }

    public function byProduct(): array
    {
        $collections = [];
        foreach ($this->all() as $collection) {
            $collections[$collection->id()->toRfc4122()] = $collection;
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('x.productId AS productId', 'IDENTITY(d.collection) AS collectionId')
            ->from(Design::class, 'd')
            ->join('d.declinations', 'x')
            ->where('d.workspace = :workspace')
            ->andWhere('d.collection IS NOT NULL')
            ->andWhere('x.productId IS NOT NULL')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
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
