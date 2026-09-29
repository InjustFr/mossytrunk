<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Etsy\EtsyListing;
use App\Domain\Etsy\EtsyListingRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineEtsyListingRepository implements EtsyListingRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(EtsyListing $listing): void
    {
        $this->entityManager->persist($listing);
    }

    public function get(Ulid $id): EtsyListing
    {
        return $this->entityManager->getRepository(EtsyListing::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw NotFound::entity('Annonce Etsy', (string) $id);
    }

    public function findByKey(string $key): ?EtsyListing
    {
        foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $pending) {
            if ($pending instanceof EtsyListing && self::keyOf($pending) === $key) {
                return $pending;
            }
        }

        return $this->entityManager->getRepository(EtsyListing::class)->findOneBy(['workspace' => $this->workspace->current(), 'listingKey' => $key]);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(EtsyListing::class)->findBy(['workspace' => $this->workspace->current()], ['seenAt' => 'DESC']);
    }

    private static function keyOf(EtsyListing $listing): string
    {
        return EtsyListing::keyOf($listing->listingId(), $listing->variation());
    }
}
