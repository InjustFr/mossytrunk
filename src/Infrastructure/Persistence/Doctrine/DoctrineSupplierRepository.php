<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Purchasing\Supplier;
use App\Domain\Purchasing\SupplierRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSupplierRepository implements SupplierRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(Supplier $supplier): void
    {
        $this->entityManager->persist($supplier);
    }

    public function get(Ulid $id): Supplier
    {
        return $this->entityManager->getRepository(Supplier::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('supplier', (string) $id);
    }

    public function findByName(string $name): ?Supplier
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Supplier::class, 's')
            ->where('s.workspace = :workspace')
            ->andWhere('LOWER(s.name) = LOWER(:name)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Supplier ? $result : null;
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Supplier::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }
}
