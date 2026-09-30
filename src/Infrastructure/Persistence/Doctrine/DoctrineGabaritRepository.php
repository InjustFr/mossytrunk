<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineGabaritRepository implements GabaritRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(Gabarit $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function get(Ulid $id): Gabarit
    {
        return $this->entityManager->getRepository(Gabarit::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('template', (string) $id);
    }

    public function findByName(string $name): ?Gabarit
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Gabarit::class, 'g')
            ->where('g.workspace = :workspace')
            ->andWhere('LOWER(g.name) = LOWER(:name)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Gabarit ? $result : null;
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Gabarit::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }
}
