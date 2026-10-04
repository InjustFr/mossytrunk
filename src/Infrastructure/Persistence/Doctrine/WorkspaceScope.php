<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Identity\Workspace;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class WorkspaceScope
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function workspace(): Workspace
    {
        return $this->workspace->current();
    }

    /**
     * @template T of QueryBuilder
     *
     * @param T $query
     *
     * @return T
     */
    public function restrict(QueryBuilder $query, string $alias): QueryBuilder
    {
        $query->andWhere($alias.'.workspace = :workspace');
        $query->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME);

        return $query;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>       $entity
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy
     *
     * @return list<T>
     */
    public function findBy(string $entity, array $criteria = [], array $orderBy = []): array
    {
        return $this->entityManager->getRepository($entity)->findBy(['workspace' => $this->workspace->current(), ...$criteria], $orderBy);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $entity
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    public function findOneBy(string $entity, array $criteria): ?object
    {
        return $this->entityManager->getRepository($entity)->findOneBy(['workspace' => $this->workspace->current(), ...$criteria]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entity
     *
     * @return T
     */
    public function get(string $entity, Ulid $id, string $kind): object
    {
        return $this->findOneBy($entity, ['id' => $id]) ?? throw new NotFound($kind, (string) $id);
    }

    /**
     * @param class-string         $entity
     * @param array<string, mixed> $criteria
     */
    public function count(string $entity, array $criteria): int
    {
        return $this->entityManager->getRepository($entity)->count(['workspace' => $this->workspace->current(), ...$criteria]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entity
     *
     * @return T|null
     */
    public function namedIgnoringCase(string $entity, string $name): ?object
    {
        $result = $this->restrict($this->entityManager->createQueryBuilder()->select('n')->from($entity, 'n'), 'n')
            ->andWhere('LOWER(n.name) = LOWER(:name)')
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof $entity ? $result : null;
    }
}
