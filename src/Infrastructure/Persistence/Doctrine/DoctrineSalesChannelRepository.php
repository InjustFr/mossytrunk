<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSalesChannelRepository implements SalesChannelRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(SalesChannel $channel): void
    {
        $this->entityManager->persist($channel);
    }

    public function remove(SalesChannel $channel): void
    {
        $this->entityManager->remove($channel);
    }

    public function get(Ulid $id): SalesChannel
    {
        return $this->entityManager->getRepository(SalesChannel::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('sales_channel', (string) $id);
    }

    public function linkedTo(string $service): ?SalesChannel
    {
        return $this->entityManager->getRepository(SalesChannel::class)->findOneBy(['service' => $service, 'workspace' => $this->workspace->current()]);
    }

    public function main(): SalesChannel
    {
        return $this->entityManager->getRepository(SalesChannel::class)->findOneBy(['main' => true, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('sales_channel', 'main');
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(SalesChannel::class)->findBy(['workspace' => $this->workspace->current()], ['main' => 'DESC', 'name' => 'ASC']);
    }
}
