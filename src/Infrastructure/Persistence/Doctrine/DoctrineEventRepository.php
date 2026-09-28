<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Event;
use App\Domain\Event\EventRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\NotFound;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineEventRepository implements EventRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Event $event): void
    {
        $this->entityManager->persist($event);
    }

    public function get(Ulid $id): Event
    {
        return $this->entityManager->find(Event::class, $id) ?? throw NotFound::entity('Événement', (string) $id);
    }

    public function findCovering(\DateTimeImmutable $moment): ?Event
    {
        $day = $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d');

        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.period.start <= :day')
            ->andWhere('e.period.end >= :day')
            ->setParameter('day', $day)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOverlapping(DateRange $period, ?Ulid $except = null): ?Event
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.period.start <= :end')
            ->andWhere('e.period.end >= :start')
            ->setParameter('start', $period->start(), Types::DATE_IMMUTABLE)
            ->setParameter('end', $period->end(), Types::DATE_IMMUTABLE)
            ->setMaxResults(1);

        if (null !== $except) {
            $query->andWhere('e.id != :except')->setParameter('except', $except, UlidType::NAME);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Event::class)->findBy([], ['period.start' => 'DESC']);
    }
}
