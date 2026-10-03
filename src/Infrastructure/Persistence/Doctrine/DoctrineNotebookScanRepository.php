<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Notebook\NotebookScan;
use App\Domain\Notebook\NotebookScanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineNotebookScanRepository implements NotebookScanRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(NotebookScan $scan): void
    {
        $this->entityManager->persist($scan);
    }

    public function ofEvent(Ulid $eventId): ?NotebookScan
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(NotebookScan::class, 's')
            ->where('s.workspace = :workspace')
            ->andWhere('s.event = :event')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('event', $eventId, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof NotebookScan ? $result : null;
    }
}
