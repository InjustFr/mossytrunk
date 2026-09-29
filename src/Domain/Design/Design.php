<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Identity\Workspace;
use App\Domain\Shared\Money;
use App\Domain\Shared\NotFound;
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
            throw InvalidDesign::emptyName('du design');
        }

        $this->name = $name;
        $this->collection = $collection;
        $this->notes = null === $notes || '' === trim($notes) ? null : trim($notes);
    }

    public function workOn(bool $current): void
    {
        $this->current = $current && !$this->isValidated();
    }

    public function decline(Gabarit $gabarit): Declination
    {
        $this->assertInProgress();
        foreach ($this->declinations as $declination) {
            if ($declination->isOn($gabarit)) {
                throw InvalidDesign::alreadyDeclined($gabarit->name());
            }
        }

        $declination = new Declination($this, $gabarit);
        $this->declinations->add($declination);

        return $declination;
    }

    public function withdraw(Ulid $declinationId): void
    {
        $this->assertInProgress();
        $this->declinations->removeElement($this->declination($declinationId));
    }

    /**
     * @param list<string> $variants
     */
    public function adjust(Ulid $declinationId, string $productName, Money $sellingPrice, Money $buyingPrice, array $variants): void
    {
        $this->assertInProgress();
        $this->declination($declinationId)->adjust($productName, $sellingPrice, $buyingPrice, $variants);
    }

    public function tick(Ulid $declinationId, string $adaptation, bool $done): void
    {
        $this->assertInProgress();
        $this->declination($declinationId)->tick($adaptation, $done);
    }

    /**
     * @return list<Declination>
     */
    public function validate(\DateTimeImmutable $validatedAt): array
    {
        $this->assertInProgress();
        $declinations = $this->declinations();
        if ([] === $declinations) {
            throw InvalidDesign::nothingToValidate($this->name);
        }

        $names = [];
        foreach ($declinations as $declination) {
            $pending = \count($declination->pendingAdaptations());
            if ($pending > 0) {
                throw InvalidDesign::adaptationsPending($declination->displayName(), $pending);
            }
            $key = mb_strtolower($declination->displayName());
            if (isset($names[$key])) {
                throw InvalidDesign::sameProductTwice($declination->displayName());
            }
            $names[$key] = true;
        }

        $this->status = DesignStatus::Validated;
        $this->validatedAt = $validatedAt;
        $this->current = false;

        return $declinations;
    }

    public function declination(Ulid $declinationId): Declination
    {
        foreach ($this->declinations as $declination) {
            if ($declination->id()->equals($declinationId)) {
                return $declination;
            }
        }

        throw NotFound::entity('Déclinaison', (string) $declinationId);
    }

    public function isValidated(): bool
    {
        return DesignStatus::Validated === $this->status;
    }

    private function assertInProgress(): void
    {
        if ($this->isValidated()) {
            throw InvalidDesign::alreadyValidated($this->name);
        }
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
