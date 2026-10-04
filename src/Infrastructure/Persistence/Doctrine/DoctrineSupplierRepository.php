<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Purchasing\Supplier;
use App\Domain\Purchasing\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSupplierRepository implements SupplierRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function add(Supplier $supplier): void
    {
        $this->entityManager->persist($supplier);
    }

    public function get(Ulid $id): Supplier
    {
        return $this->scope->get(Supplier::class, $id, 'supplier');
    }

    public function findByName(string $name): ?Supplier
    {
        return $this->scope->namedIgnoringCase(Supplier::class, $name);
    }

    public function all(): array
    {
        return $this->scope->findBy(Supplier::class, orderBy: ['name' => 'ASC']);
    }
}
