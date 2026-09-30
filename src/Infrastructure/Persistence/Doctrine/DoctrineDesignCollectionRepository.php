<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDesignCollectionRepository implements DesignCollectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(DesignCollection $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(DesignCollection $collection): void
    {
        $this->entityManager->remove($collection);
    }

    public function get(Ulid $id): DesignCollection
    {
        return $this->entityManager->getRepository(DesignCollection::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('collection', (string) $id);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(DesignCollection::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }
}
