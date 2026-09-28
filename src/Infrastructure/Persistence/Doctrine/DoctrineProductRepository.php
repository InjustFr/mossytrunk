<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineProductRepository implements ProductRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Product $product): void
    {
        $this->entityManager->persist($product);
    }

    public function get(Ulid $id): Product
    {
        return $this->entityManager->find(Product::class, $id) ?? throw NotFound::entity('Produit', (string) $id);
    }

    public function findByReference(string $reference): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['reference' => $reference]);
    }

    public function findByName(string $name): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $name]);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Product::class)->findBy([], ['name' => 'ASC']);
    }
}
