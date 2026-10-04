<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Product\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineGabaritRepository implements GabaritRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(Gabarit $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(Gabarit $gabarit): void
    {
        $this->entityManager->remove($gabarit);
    }

    public function get(Ulid $id): Gabarit
    {
        return $this->scope->get(Gabarit::class, $id, 'template');
    }

    public function findByName(string $name): ?Gabarit
    {
        return $this->scope->namedIgnoringCase(Gabarit::class, $name);
    }

    public function ofType(ProductType $type): array
    {
        return $this->scope->findBy(Gabarit::class, ['type' => $type], ['name' => 'ASC']);
    }

    public function all(): array
    {
        return $this->scope->findBy(Gabarit::class, orderBy: ['name' => 'ASC']);
    }
}
