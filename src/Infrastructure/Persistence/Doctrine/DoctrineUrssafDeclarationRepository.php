<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Accounting\UrssafDeclarationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUrssafDeclarationRepository implements UrssafDeclarationRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(UrssafDeclaration $declaration): void
    {
        $this->entityManager->persist($declaration);
    }

    public function remove(UrssafDeclaration $declaration): void
    {
        $this->entityManager->remove($declaration);
    }

    public function find(string $period): ?UrssafDeclaration
    {
        return $this->entityManager->getRepository(UrssafDeclaration::class)->findOneBy(['workspace' => $this->workspace->current(), 'period' => $period]);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(UrssafDeclaration::class)->findBy(['workspace' => $this->workspace->current()]);
    }
}
