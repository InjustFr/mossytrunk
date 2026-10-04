<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Accounting\UrssafDeclarationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUrssafDeclarationRepository implements UrssafDeclarationRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
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
        return $this->scope->findOneBy(UrssafDeclaration::class, ['period' => $period]);
    }

    public function all(): array
    {
        return $this->scope->findBy(UrssafDeclaration::class);
    }
}
