<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineEventRepository implements EventRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(Event $event): void
    {
        $this->entityManager->persist($event);
    }

    public function get(Ulid $id): Event
    {
        return $this->entityManager->getRepository(Event::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('event', (string) $id);
    }

    public function findCovering(\DateTimeImmutable $moment): ?Event
    {
        $day = $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d');

        $result = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.period.start <= :day')
            ->andWhere('e.period.end >= :day')
            ->andWhere('e.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('day', $day)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Event ? $result : null;
    }

    public function findOverlapping(DateRange $period, ?Ulid $except = null): ?Event
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.period.start <= :end')
            ->andWhere('e.period.end >= :start')
            ->andWhere('e.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('start', $period->start(), Types::DATE_IMMUTABLE)
            ->setParameter('end', $period->end(), Types::DATE_IMMUTABLE)
            ->setMaxResults(1);

        if (null !== $except) {
            $query->andWhere('e.id != :except')->setParameter('except', $except, UlidType::NAME);
        }

        $result = $query->getQuery()->getOneOrNullResult();

        return $result instanceof Event ? $result : null;
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Event::class)->findBy(['workspace' => $this->workspace->current()], ['period.start' => 'DESC']);
    }
}
