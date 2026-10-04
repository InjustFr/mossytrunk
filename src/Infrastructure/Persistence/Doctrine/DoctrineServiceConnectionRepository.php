<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineServiceConnectionRepository implements ServiceConnectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
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
        return $this->scope->findOneBy(ServiceConnection::class, ['service' => $service]);
    }

    public function get(string $service): ServiceConnection
    {
        return $this->find($service) ?? throw new NotFound('service', $service);
    }

    public function all(): array
    {
        return $this->scope->findBy(ServiceConnection::class, orderBy: ['createdAt' => 'ASC']);
    }
}
