<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\NotFound;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Order $order): void
    {
        $this->entityManager->persist($order);
    }

    public function remove(Order $order): void
    {
        $this->entityManager->remove($order);
    }

    public function get(Ulid $id): Order
    {
        return $this->entityManager->find(Order::class, $id) ?? throw NotFound::entity('Commande', (string) $id);
    }

    public function list(?Ulid $eventId = null): array
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('o', 'l', 'e')
            ->from(Order::class, 'o')
            ->join('o.event', 'e')
            ->leftJoin('o.lines', 'l')
            ->orderBy('o.placedAt', 'DESC');

        if (null !== $eventId) {
            $query->where('e.id = :event')->setParameter('event', $eventId, UlidType::NAME);
        }

        return $query->getQuery()->getResult();
    }

    public function importedSumUpTransactionCodes(array $transactionCodes): array
    {
        if ([] === $transactionCodes) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('o.sumUpTransactionCode')
            ->from(Order::class, 'o')
            ->where('o.sumUpTransactionCode IN (:codes)')
            ->setParameter('codes', $transactionCodes, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function countOutside(Ulid $eventId, DateRange $period): int
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);
        $from = new \DateTimeImmutable($period->start()->format('Y-m-d'), $timezone);
        $until = new \DateTimeImmutable($period->end()->format('Y-m-d').' +1 day', $timezone);

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.event = :event')
            ->andWhere('o.placedAt < :from OR o.placedAt >= :until')
            ->setParameter('event', $eventId, UlidType::NAME)
            ->setParameter('from', $from, Types::DATETIMETZ_IMMUTABLE)
            ->setParameter('until', $until, Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
