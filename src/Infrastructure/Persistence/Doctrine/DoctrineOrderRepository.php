<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Order\ImportedSale;
use App\Domain\Order\Order;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
            ?? throw new NotFound('order', (string) $id);
    }

    public function list(?Ulid $eventId = null): array
    {
        return $this->orders($eventId)->getQuery()->getResult();
    }

    public function sales(?Ulid $eventId = null): array
    {
        return $this->orders($eventId)->andWhere('o.refundedAt IS NULL')->getQuery()->getResult();
    }

    public function salesWithin(DateRange $period): array
    {
        [$from, $until] = $this->bounds($period);

        return $this->orders(null)
            ->andWhere('o.refundedAt IS NULL')
            ->andWhere('o.placedAt >= :from AND o.placedAt < :until')
            ->setParameter('from', $from, Types::DATETIMETZ_IMMUTABLE)
            ->setParameter('until', $until, Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    public function mergeCandidatesOf(Order $order): array
    {
        $query = $this->orders($order->event()?->id())
            ->andWhere('o.refundedAt IS NULL')
            ->andWhere('o.source = :source')
            ->andWhere('o.id != :order')
            ->setParameter('source', $order->source())
            ->setParameter('order', $order->id(), UlidType::NAME);

        if (null === $order->event()) {
            $query->andWhere('o.event IS NULL');
        }

        return $query->getQuery()->getResult();
    }

    private function orders(?Ulid $eventId): QueryBuilder
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('o', 'l', 'e', 'i')
            ->from(Order::class, 'o')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.lines', 'l')
            ->leftJoin('o.importedSales', 'i')
            ->where('o.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('o.placedAt', 'DESC');

        if (null !== $eventId) {
            $query->andWhere('e.id = :event')->setParameter('event', $eventId, UlidType::NAME);
        }

        return $query;
    }

    public function selling(Ulid $productId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o', 'l', 'e', 'i')
            ->from(Order::class, 'o')
            ->join('o.lines', 'l')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.importedSales', 'i')
            ->where('o.workspace = :workspace')
            ->andWhere('o.id IN (SELECT IDENTITY(s.order) FROM '.OrderLine::class.' s WHERE s.productId = :product)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('product', $productId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function using(Ulid $supplyId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o', 's', 'e')
            ->from(Order::class, 'o')
            ->join('o.supplies', 's')
            ->leftJoin('o.event', 'e')
            ->where('o.workspace = :workspace')
            ->andWhere('s.productId = :supply')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('supply', $supplyId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function importedExternalIds(string $source, array $externalIds): array
    {
        if ([] === $externalIds) {
            return [];
        }

        $ids = $this->entityManager->createQueryBuilder()
            ->select('s.externalId')
            ->from(ImportedSale::class, 's')
            ->where('s.externalId IN (:ids)')
            ->andWhere('s.source = :source')
            ->andWhere('s.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->setParameter('source', $source)
            ->setParameter('ids', $externalIds, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map(static fn (mixed $id): string => \is_scalar($id) ? (string) $id : '', $ids));
    }

    public function countWithoutEventOn(Ulid $channelId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.channel = :channel')
            ->andWhere('o.event IS NULL')
            ->andWhere('o.workspace = :workspace')
            ->setParameter('channel', $channelId, UlidType::NAME)
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOutside(Ulid $eventId, DateRange $period): int
    {
        [$from, $until] = $this->bounds($period);

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

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function bounds(DateRange $period): array
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);

        return [
            new \DateTimeImmutable($period->start()->format('Y-m-d'), $timezone),
            new \DateTimeImmutable($period->end()->format('Y-m-d').' +1 day', $timezone),
        ];
    }
}
