<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\LoadedAggregates;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLoadedAggregates implements LoadedAggregates
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forget(): void
    {
        $this->entityManager->clear();
    }
}
