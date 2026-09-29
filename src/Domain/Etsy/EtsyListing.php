<?php

declare(strict_types=1);

namespace App\Domain\Etsy;

use App\Domain\Identity\Workspace;
use App\Domain\Product\SellableItem;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'etsy_listing')]
#[ORM\UniqueConstraint(name: 'etsy_listing_workspace_key', columns: ['workspace_id', 'listing_key'])]
class EtsyListing
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 300)]
    private string $listingKey;

    #[ORM\Column(length: 32)]
    private string $listingId;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variation;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    private ?Ulid $productId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $seenAt;

    private function __construct(Workspace $workspace, string $listingId, string $title, ?string $variation, \DateTimeImmutable $seenAt)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->listingKey = self::keyOf($listingId, $variation);
        $this->listingId = $listingId;
        $this->title = mb_substr($title, 0, 255);
        $this->variation = $variation;
        $this->seenAt = $seenAt;
    }

    public static function seen(Workspace $workspace, string $listingId, string $title, ?string $variation, \DateTimeImmutable $seenAt): self
    {
        return new self($workspace, $listingId, $title, $variation, $seenAt);
    }

    public static function keyOf(string $listingId, ?string $variation): string
    {
        return mb_substr($listingId.'|'.mb_strtolower(trim((string) $variation)), 0, 300);
    }

    public function seenAgain(string $title, \DateTimeImmutable $seenAt): void
    {
        $this->title = mb_substr($title, 0, 255);
        $this->seenAt = $seenAt;
    }

    public function link(SellableItem $item): void
    {
        $this->productId = $item->productId;
        $this->variant = $item->variant;
    }

    public function isLinked(): bool
    {
        return null !== $this->productId;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function listingId(): string
    {
        return $this->listingId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function variation(): ?string
    {
        return $this->variation;
    }

    public function productId(): ?Ulid
    {
        return $this->productId;
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function seenAt(): \DateTimeImmutable
    {
        return $this->seenAt;
    }
}
