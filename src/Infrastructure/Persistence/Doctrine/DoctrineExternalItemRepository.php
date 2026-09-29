<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Integration\ExternalItem;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineExternalItemRepository implements ExternalItemRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(ExternalItem $item): void
    {
        $this->entityManager->persist($item);
    }

    public function get(Ulid $id): ExternalItem
    {
        return $this->entityManager->getRepository(ExternalItem::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw NotFound::entity('Article', (string) $id);
    }

    public function ofService(string $service): array
    {
        return $this->entityManager->getRepository(ExternalItem::class)->findBy(['workspace' => $this->workspace->current(), 'service' => $service], ['seenAt' => 'DESC']);
    }

    public function unlinkedCount(string $service): int
    {
        return $this->entityManager->getRepository(ExternalItem::class)->count(['workspace' => $this->workspace->current(), 'service' => $service, 'productId' => null]);
    }
}
