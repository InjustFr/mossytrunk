<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Product\Product;
use App\Domain\Product\ProductKind;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineProductRepository implements ProductRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
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
        return $this->find($id) ?? throw new NotFound('product', (string) $id);
    }

    public function find(Ulid $id): ?Product
    {
        $product = $this->entityManager->find(Product::class, $id);

        return null !== $product && $product->workspace()->id()->equals($this->scope->workspace()->id()) ? $product : null;
    }

    public function findByReference(string $reference): ?Product
    {
        return $this->scope->findOneBy(Product::class, ['reference' => $reference]);
    }

    public function ofType(ProductType $type): array
    {
        return $this->scope->findBy(Product::class, ['type' => $type]);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $products = $this->scope->restrict($this->entityManager->createQueryBuilder()->select('p', 't')->from(Product::class, 'p'), 'p')
            ->leftJoin('p.type', 't')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', array_map(static fn (Ulid $id): string => $id->toRfc4122(), $ids), ArrayParameterType::STRING)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($products as $product) {
            $byId[(string) $product->id()] = $product;
        }

        return $byId;
    }

    public function all(): array
    {
        return $this->allQuery()->getQuery()->getResult();
    }

    public function catalogue(): array
    {
        $products = $this->all();
        if ([] !== $products) {
            $this->entityManager->createQueryBuilder()
                ->select('p', 'h')
                ->from(Product::class, 'p')
                ->leftJoin('p.priceHistory', 'h')
                ->where('p.id IN (:ids)')
                ->setParameter('ids', array_map(static fn (Product $product): string => $product->id()->toRfc4122(), $products))
                ->getQuery()
                ->getResult();
        }

        return $products;
    }

    public function articles(): array
    {
        return $this->allQuery()
            ->andWhere('p.kind = :article')
            ->setParameter('article', ProductKind::Article->value)
            ->getQuery()
            ->getResult();
    }

    private function allQuery(): QueryBuilder
    {
        return $this->scope->restrict($this->entityManager->createQueryBuilder()->select('p', 't', 'cp', 'c')->from(Product::class, 'p'), 'p')
            ->leftJoin('p.type', 't')
            ->leftJoin('p.channelPrices', 'cp')
            ->leftJoin('cp.channel', 'c')
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('p.name', 'ASC');
    }
}
