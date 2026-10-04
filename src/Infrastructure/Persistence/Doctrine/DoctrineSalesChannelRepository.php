<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSalesChannelRepository implements SalesChannelRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
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
        return $this->scope->get(SalesChannel::class, $id, 'sales_channel');
    }

    public function linkedTo(string $service): ?SalesChannel
    {
        return $this->scope->findOneBy(SalesChannel::class, ['service' => $service]);
    }

    public function main(): SalesChannel
    {
        return $this->scope->findOneBy(SalesChannel::class, ['main' => true]) ?? throw new NotFound('sales_channel', 'main');
    }

    public function all(): array
    {
        return $this->scope->findBy(SalesChannel::class, orderBy: ['main' => 'DESC', 'name' => 'ASC']);
    }
}
