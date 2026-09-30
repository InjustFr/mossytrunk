<?php

declare(strict_types=1);

namespace App\Domain\Integration;

use App\Domain\Identity\Workspace;
use App\Domain\Product\SellableItem;
use App\Domain\Product\VariantLabel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'external_item')]
#[ORM\UniqueConstraint(name: 'external_item_workspace_service_key', columns: ['workspace_id', 'service', 'item_key'])]
class ExternalItem
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 32)]
    private string $service;

    #[ORM\Column(length: 300)]
    private string $itemKey;

    #[ORM\Column(length: 255)]
    private string $externalRef;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variation;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    private ?Ulid $productId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $seenAt;

    private function __construct(Workspace $workspace, string $service, string $externalRef, string $label, ?string $variation, \DateTimeImmutable $seenAt)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->service = $service;
        $this->itemKey = self::keyOf($externalRef, $variation);
        $this->externalRef = mb_substr($externalRef, 0, 255);
        $this->label = mb_substr($label, 0, 255);
        $this->variation = null === $variation ? null : mb_substr($variation, 0, 255);
        $this->seenAt = $seenAt;
    }

    public static function seen(Workspace $workspace, string $service, string $externalRef, string $label, ?string $variation, \DateTimeImmutable $seenAt): self
    {
        return new self($workspace, $service, $externalRef, $label, $variation, $seenAt);
    }

    public static function keyOf(string $externalRef, ?string $variation): string
    {
        return mb_substr($externalRef.'|'.mb_strtolower(trim((string) $variation)), 0, 300);
    }

    public function seenAgain(string $label, \DateTimeImmutable $seenAt): void
    {
        $this->label = mb_substr($label, 0, 255);
        $this->seenAt = $seenAt;
    }

    public function link(SellableItem $item): void
    {
        $this->productId = $item->productId;
        $this->variant = $item->variant;
    }

    public function unlink(): void
    {
        $this->productId = null;
        $this->variant = null;
    }

    public function isLinkedTo(Ulid $productId, ?string $variant): bool
    {
        return null !== $this->productId && $this->productId->equals($productId) && VariantLabel::same($this->variant, $variant);
    }

    public function isLinked(): bool
    {
        return null !== $this->productId;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function service(): string
    {
        return $this->service;
    }

    public function itemKey(): string
    {
        return $this->itemKey;
    }

    public function externalRef(): string
    {
        return $this->externalRef;
    }

    public function label(): string
    {
        return $this->label;
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
