<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Reference\ReferenceFormat;
use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineReferenceFormatRepository implements ReferenceFormatRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function of(ReferenceKind $kind): ReferenceFormat
    {
        return $this->entityManager->getRepository(ReferenceFormat::class)->findOneBy(['workspace' => $this->workspace->current(), 'kind' => $kind])
            ?? $this->pending($kind)
            ?? $this->standard($kind);
    }

    public function add(ReferenceFormat $format): void
    {
        $this->entityManager->persist($format);
    }

    private function pending(ReferenceKind $kind): ?ReferenceFormat
    {
        foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ReferenceFormat && $kind === $entity->kind()) {
                return $entity;
            }
        }

        return null;
    }

    private function standard(ReferenceKind $kind): ReferenceFormat
    {
        $format = ReferenceFormat::standard($this->workspace->current(), $kind);
        $this->entityManager->persist($format);

        return $format;
    }
}
