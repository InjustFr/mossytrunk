<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use App\Domain\Event\Event;
use App\Domain\Identity\Workspace;
use App\Domain\Notebook\Exception\EmptyNotebook;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'notebook_scan')]
#[ORM\UniqueConstraint(name: 'notebook_scan_event', columns: ['event_id'])]
class NotebookScan
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $scannedAt;

    #[ORM\Column]
    private int $pages;

    /** @var list<array{page: int, lines: list<array{written: string, quantity: int, label: string, productId: ?string, variant: ?string, typeId: ?string}>}> */
    #[ORM\Column(type: Types::JSON)]
    private array $entries;

    /**
     * @param list<NotebookEntry> $entries
     */
    private function __construct(Event $event, \DateTimeImmutable $scannedAt, int $pages, array $entries)
    {
        $this->id = new Ulid();
        $this->workspace = $event->workspace();
        $this->event = $event;
        $this->reread($scannedAt, $pages, $entries);
    }

    /**
     * @param list<NotebookEntry> $entries
     */
    public static function read(Event $event, \DateTimeImmutable $scannedAt, int $pages, array $entries): self
    {
        return new self($event, $scannedAt, $pages, $entries);
    }

    /**
     * @param list<NotebookEntry> $entries
     */
    public function reread(\DateTimeImmutable $scannedAt, int $pages, array $entries): void
    {
        if ([] === $entries) {
            throw new EmptyNotebook();
        }

        $this->scannedAt = $scannedAt;
        $this->pages = $pages;
        $this->entries = array_map(static fn (NotebookEntry $entry): array => $entry->toArray(), $entries);
    }

    /**
     * @param list<RecordedOrder> $orders
     */
    public function reconcile(array $orders): Reconciliation
    {
        return NotebookReconciliation::between($this->entries(), $orders);
    }

    /**
     * @return list<NotebookEntry>
     */
    public function entries(): array
    {
        return array_map(NotebookEntry::fromArray(...), $this->entries);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function event(): Event
    {
        return $this->event;
    }

    public function scannedAt(): \DateTimeImmutable
    {
        return $this->scannedAt;
    }

    public function pages(): int
    {
        return $this->pages;
    }
}
