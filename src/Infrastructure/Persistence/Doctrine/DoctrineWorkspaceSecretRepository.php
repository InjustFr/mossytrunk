<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Identity\SecretName;
use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceSecret;
use App\Domain\Identity\WorkspaceSecretRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineWorkspaceSecretRepository implements WorkspaceSecretRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(WorkspaceSecret $secret): void
    {
        $this->entityManager->persist($secret);
    }

    public function remove(WorkspaceSecret $secret): void
    {
        $this->entityManager->remove($secret);
    }

    public function find(Workspace $workspace, SecretName $name): ?WorkspaceSecret
    {
        return $this->entityManager->getRepository(WorkspaceSecret::class)->findOneBy(['workspace' => $workspace, 'name' => $name]);
    }
}
