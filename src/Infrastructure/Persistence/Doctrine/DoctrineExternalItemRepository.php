<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Integration\ExternalItem;
use App\Domain\Integration\ExternalItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineExternalItemRepository implements ExternalItemRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(ExternalItem $item): void
    {
        $this->entityManager->persist($item);
    }

    public function get(Ulid $id): ExternalItem
    {
        return $this->scope->get(ExternalItem::class, $id, 'external_item');
    }

    public function ofService(string $service): array
    {
        return $this->scope->findBy(ExternalItem::class, ['service' => $service], ['seenAt' => 'DESC']);
    }

    public function linkedTo(Ulid $productId): array
    {
        return $this->scope->findBy(ExternalItem::class, ['productId' => $productId]);
    }

    public function unlinkedCount(string $service): int
    {
        return $this->scope->count(ExternalItem::class, ['service' => $service, 'productId' => null]);
    }
}
