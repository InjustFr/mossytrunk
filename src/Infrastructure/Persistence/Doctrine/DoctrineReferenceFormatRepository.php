<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Reference\ReferenceFormat;
use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineReferenceFormatRepository implements ReferenceFormatRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceScope $scope,
    ) {
    }

    public function of(ReferenceKind $kind): ReferenceFormat
    {
        return $this->known($kind)
            ?? $this->scope->findOneBy(ReferenceFormat::class, ['kind' => $kind])
            ?? $this->standard($kind);
    }

    public function add(ReferenceFormat $format): void
    {
        $this->entityManager->persist($format);
    }

    private function known(ReferenceKind $kind): ?ReferenceFormat
    {
        $workspace = $this->scope->workspace();
        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap()[ReferenceFormat::class] ?? [] as $format) {
            if ($format instanceof ReferenceFormat && $format->isFor($workspace, $kind)) {
                return $format;
            }
        }

        return null;
    }

    private function standard(ReferenceKind $kind): ReferenceFormat
    {
        $format = ReferenceFormat::standard($this->scope->workspace(), $kind);
        $this->entityManager->persist($format);

        return $format;
    }
}
