<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineWorkspaceRepository implements WorkspaceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Workspace $workspace): void
    {
        $this->entityManager->persist($workspace);
    }

    public function get(Ulid $id): Workspace
    {
        return $this->entityManager->find(Workspace::class, $id) ?? throw NotFound::entity('Espace de travail', (string) $id);
    }

    public function findByName(string $name): ?Workspace
    {
        return $this->entityManager->getRepository(Workspace::class)->findOneBy(['name' => trim($name)]);
    }
}
