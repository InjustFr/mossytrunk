<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineProductTypeRepository implements ProductTypeRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(ProductType $type): void
    {
        $this->entityManager->persist($type);
    }

    public function remove(ProductType $type): void
    {
        $this->entityManager->remove($type);
    }

    public function get(Ulid $id): ProductType
    {
        return $this->scope->get(ProductType::class, $id, 'product_type');
    }

    public function findByName(string $name): ?ProductType
    {
        return $this->scope->namedIgnoringCase(ProductType::class, $name);
    }

    public function codeExists(string $code): bool
    {
        return null !== $this->scope->findOneBy(ProductType::class, ['code' => $code]);
    }

    public function all(): array
    {
        return $this->scope->findBy(ProductType::class, orderBy: ['name' => 'ASC']);
    }
}
