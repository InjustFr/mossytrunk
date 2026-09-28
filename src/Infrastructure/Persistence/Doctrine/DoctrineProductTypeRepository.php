<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineProductTypeRepository implements ProductTypeRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(ProductType $type): void
    {
        $this->entityManager->persist($type);
    }

    public function get(Ulid $id): ProductType
    {
        return $this->entityManager->getRepository(ProductType::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw NotFound::entity('Type de produit', (string) $id);
    }

    public function findByName(string $name): ?ProductType
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(ProductType::class, 't')
            ->where('LOWER(t.name) = LOWER(:name)')
            ->andWhere('t.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function codeExists(string $code): bool
    {
        return null !== $this->entityManager->getRepository(ProductType::class)->findOneBy(['code' => $code, 'workspace' => $this->workspace->current()]);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(ProductType::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }
}
