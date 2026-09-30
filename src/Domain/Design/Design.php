<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Design\Exception\AdaptationsPending;
use App\Domain\Design\Exception\AlreadyDeclined;
use App\Domain\Design\Exception\EmptyDesignName;
use App\Domain\Design\Exception\NothingToValidate;
use App\Domain\Design\Exception\SameProductTwice;
use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'design')]
class Design
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: DesignCollection::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DesignCollection $collection = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 16, enumType: DesignStatus::class)]
    private DesignStatus $status = DesignStatus::InProgress;

    #[ORM\Column]
    private bool $current = true;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Declination> */
    #[ORM\OneToMany(targetEntity: Declination::class, mappedBy: 'design', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $declinations;

    private function __construct(Workspace $workspace, string $name, ?DesignCollection $collection, ?string $notes)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->createdAt = new \DateTimeImmutable();
        $this->declinations = new ArrayCollection();
        $this->describe($name, $collection, $notes);
    }

    public static function start(Workspace $workspace, string $name, ?DesignCollection $collection = null, ?string $notes = null): self
    {
        return new self($workspace, $name, $collection, $notes);
    }

    public function describe(string $name, ?DesignCollection $collection, ?string $notes): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyDesignName('design');
        }

        $this->name = $name;
        $this->collection = $collection;
        $this->notes = null === $notes || '' === trim($notes) ? null : trim($notes);
    }

    public function leaveCollection(): void
    {
        $this->collection = null;
    }

    public function workOn(bool $current): void
    {
        $this->current = $current && !$this->isValidated();
    }

    public static function fromProduct(Workspace $workspace, Product $product, Gabarit $gabarit, ?DesignCollection $collection, \DateTimeImmutable $at): self
    {
        $design = new self($workspace, $product->name(), $collection, null);
        $design->adopt($product, $gabarit, $at);

        return $design;
    }

    public function decline(Gabarit $gabarit): Declination
    {
        $this->assertNotDeclinedOn($gabarit);

        $declination = new Declination($this, $gabarit);
        $this->declinations->add($declination);
        $this->status = DesignStatus::InProgress;
        $this->current = true;

        return $declination;
    }

    public function adopt(Product $product, Gabarit $gabarit, \DateTimeImmutable $at): Declination
    {
        $this->assertNotDeclinedOn($gabarit);

        $declination = new Declination($this, $gabarit);
        $declination->adopt($product);
        $this->declinations->add($declination);
        if ([] === $this->pendingDeclinations()) {
            $this->markValidated($at);
        }

        return $declination;
    }

    public function withdraw(Ulid $declinationId): void
    {
        $declination = $this->declination($declinationId);
        $declination->assertEditable();
        $this->declinations->removeElement($declination);
        if ([] === $this->pendingDeclinations() && [] !== $this->declinations()) {
            $this->status = DesignStatus::Validated;
        }
    }

    /**
     * @param list<string> $variants
     */
    public function adjust(Ulid $declinationId, string $productName, Money $sellingPrice, array $variants): void
    {
        $declination = $this->declination($declinationId);
        $declination->assertEditable();
        $declination->adjust($productName, $sellingPrice, $variants);
    }

    public function tick(Ulid $declinationId, string $adaptation, bool $done): void
    {
        $declination = $this->declination($declinationId);
        $declination->assertEditable();
        $declination->tick($adaptation, $done);
    }

    /**
     * @return list<Declination>
     */
    public function validate(\DateTimeImmutable $validatedAt): array
    {
        $pending = $this->pendingDeclinations();
        if ([] === $pending) {
            throw new NothingToValidate($this->name);
        }

        $names = [];
        foreach ($this->declinations() as $declination) {
            $key = mb_strtolower($declination->displayName());
            if (isset($names[$key])) {
                throw new SameProductTwice($declination->displayName());
            }
            $names[$key] = true;
        }
        foreach ($pending as $declination) {
            $count = \count($declination->pendingAdaptations());
            if ($count > 0) {
                throw new AdaptationsPending($declination->displayName(), $count);
            }
        }

        $this->markValidated($validatedAt);

        return $pending;
    }

    /**
     * @return list<Declination>
     */
    public function pendingDeclinations(): array
    {
        return array_values(array_filter($this->declinations(), static fn (Declination $declination): bool => !$declination->isProduced()));
    }

    public function hasProducts(): bool
    {
        return \count($this->pendingDeclinations()) < \count($this->declinations());
    }

    private function markValidated(\DateTimeImmutable $at): void
    {
        $this->status = DesignStatus::Validated;
        $this->validatedAt = $at;
        $this->current = false;
    }

    private function assertNotDeclinedOn(Gabarit $gabarit): void
    {
        foreach ($this->declinations as $declination) {
            if ($declination->isOn($gabarit)) {
                throw new AlreadyDeclined($gabarit->name());
            }
        }
    }

    public function renameVariant(ProductType $type, string $from, string $to): void
    {
        foreach ($this->declinations as $declination) {
            if ($declination->gabarit()->type() === $type) {
                $declination->renameVariant($from, $to);
            }
        }
    }

    public function usesVariant(ProductType $type, string $variant): bool
    {
        return $this->declinations->exists(static fn (int $key, Declination $declination): bool => $declination->gabarit()->type() === $type && $declination->usesVariant($variant));
    }

    public function declination(Ulid $declinationId): Declination
    {
        foreach ($this->declinations as $declination) {
            if ($declination->id()->equals($declinationId)) {
                return $declination;
            }
        }

        throw new NotFound('adaptation', (string) $declinationId);
    }

    public function isValidated(): bool
    {
        return DesignStatus::Validated === $this->status;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function collection(): ?DesignCollection
    {
        return $this->collection;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): DesignStatus
    {
        return $this->status;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }

    public function validatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    /**
     * @return list<Declination>
     */
    public function declinations(): array
    {
        return array_values($this->declinations->toArray());
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
