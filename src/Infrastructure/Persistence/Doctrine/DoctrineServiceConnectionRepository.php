<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineServiceConnectionRepository implements ServiceConnectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(ServiceConnection $connection): void
    {
        $this->entityManager->persist($connection);
    }

    public function remove(ServiceConnection $connection): void
    {
        $this->entityManager->remove($connection);
    }

    public function find(string $service): ?ServiceConnection
    {
        return $this->entityManager->getRepository(ServiceConnection::class)->findOneBy(['workspace' => $this->workspace->current(), 'service' => $service]);
    }

    public function get(string $service): ServiceConnection
    {
        return $this->find($service) ?? throw NotFound::entity('Service', $service);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(ServiceConnection::class)->findBy(['workspace' => $this->workspace->current()], ['createdAt' => 'ASC']);
    }
}
