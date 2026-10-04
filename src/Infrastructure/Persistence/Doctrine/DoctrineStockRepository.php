<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Product\Product;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockItem;
use App\Domain\Stock\StockLot;
use App\Domain\Stock\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineStockRepository implements StockRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function remove(StockItem $item): void
    {
        $this->entityManager->remove($item);
    }

    public function for(Product $product, ?string $variant): StockItem
    {
        $item = $this->find($product->id(), $variant);
        if (null === $item) {
            $item = StockItem::open($product, $variant);
            $this->entityManager->persist($item);
        }

        return $item;
    }

    public function find(Ulid $productId, ?string $variant): ?StockItem
    {
        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap()[StockItem::class] ?? [] as $known) {
            if ($known instanceof StockItem && $known->isFor($productId, $variant)) {
                return $known;
            }
        }

        $query = $this->items()
            ->andWhere('p.id = :product')
            ->setParameter('product', $productId, UlidType::NAME);
        if (null === $variant) {
            $query->andWhere('s.variant IS NULL');
        } else {
            $query->andWhere('s.variant = :variant')->setParameter('variant', $variant);
        }

        $result = $query->getQuery()->getOneOrNullResult();

        return $result instanceof StockItem ? $result : null;
    }

    public function ofProduct(Ulid $productId): array
    {
        return $this->items()
            ->andWhere('p.id = :product')
            ->setParameter('product', $productId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function receivedFrom(Ulid $supplierOrderId): array
    {
        return $this->items()
            ->andWhere('EXISTS (SELECT 1 FROM '.StockLot::class.' r WHERE r.item = s AND r.sourceId = :order AND r.origin = :origin)')
            ->setParameter('order', $supplierOrderId, UlidType::NAME)
            ->setParameter('origin', LotOrigin::SupplierOrder->value)
            ->getQuery()
            ->getResult();
    }

    public function all(): array
    {
        return $this->items()->getQuery()->getResult();
    }

    public function byProduct(): array
    {
        $byProduct = [];
        foreach ($this->all() as $item) {
            $byProduct[(string) $item->product()->id()][] = $item;
        }

        return $byProduct;
    }

    private function items(): QueryBuilder
    {
        return $this->scope->restrict($this->entityManager->createQueryBuilder()->select('s', 'l', 'p')->from(StockItem::class, 's'), 's')
            ->join('s.product', 'p')
            ->leftJoin('s.lots', 'l');
    }
}
