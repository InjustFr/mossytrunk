<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineProductRepository implements ProductRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(Product $product): void
    {
        $this->entityManager->persist($product);
    }

    public function remove(Product $product): void
    {
        $this->entityManager->remove($product);
    }

    public function get(Ulid $id): Product
    {
        $product = $this->entityManager->find(Product::class, $id);
        if (null === $product || !$product->workspace()->id()->equals($this->workspace->current()->id())) {
            throw new NotFound('product', (string) $id);
        }

        return $product;
    }

    public function findByReference(string $reference): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['reference' => $reference, 'workspace' => $this->workspace->current()]);
    }

    public function findByName(string $name): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $name, 'workspace' => $this->workspace->current()]);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('p', 't')
            ->from(Product::class, 'p')
            ->leftJoin('p.type', 't')
            ->where('p.id IN (:ids)')
            ->andWhere('p.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('ids', array_map(static fn (Ulid $id): string => $id->toRfc4122(), $ids), \Doctrine\DBAL\ArrayParameterType::STRING)
            ->getQuery()
            ->getResult();
    }

    public function all(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p', 't')
            ->from(Product::class, 'p')
            ->leftJoin('p.type', 't')
            ->where('p.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
