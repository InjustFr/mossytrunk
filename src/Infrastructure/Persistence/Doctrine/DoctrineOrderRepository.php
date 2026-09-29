<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Order\Order;
use App\Domain\Order\OrderLine;
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
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
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
        return $this->entityManager->getRepository(Order::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw NotFound::entity('Commande', (string) $id);
    }

    public function list(?Ulid $eventId = null): array
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('o', 'l', 'e')
            ->from(Order::class, 'o')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.lines', 'l')
            ->where('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('o.placedAt', 'DESC');

        if (null !== $eventId) {
            $query->andWhere('e.id = :event')->setParameter('event', $eventId, UlidType::NAME);
        }

        return $query->getQuery()->getResult();
    }

    public function selling(Ulid $productId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o', 'l')
            ->from(Order::class, 'o')
            ->join('o.lines', 'l')
            ->where('o.workspace = :workspace')
            ->andWhere('o.id IN (SELECT IDENTITY(s.order) FROM '.OrderLine::class.' s WHERE s.productId = :product)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('product', $productId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function importedEtsyReceiptIds(array $receiptIds): array
    {
        if ([] === $receiptIds) {
            return [];
        }

        $ids = $this->entityManager->createQueryBuilder()
            ->select('o.etsyReceiptId')
            ->from(Order::class, 'o')
            ->where('o.etsyReceiptId IN (:ids)')
            ->andWhere('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('ids', $receiptIds, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map(static fn (mixed $id): string => \is_scalar($id) ? (string) $id : '', $ids));
    }

    public function importedSumUpTransactionCodes(array $transactionCodes): array
    {
        if ([] === $transactionCodes) {
            return [];
        }

        $codes = $this->entityManager->createQueryBuilder()
            ->select('o.sumUpTransactionCode')
            ->from(Order::class, 'o')
            ->where('o.sumUpTransactionCode IN (:codes)')
            ->andWhere('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('codes', $transactionCodes, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map(static fn (mixed $code): string => \is_scalar($code) ? (string) $code : '', $codes));
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
            ->andWhere('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->andWhere('o.placedAt < :from OR o.placedAt >= :until')
            ->setParameter('event', $eventId, UlidType::NAME)
            ->setParameter('from', $from, Types::DATETIMETZ_IMMUTABLE)
            ->setParameter('until', $until, Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
