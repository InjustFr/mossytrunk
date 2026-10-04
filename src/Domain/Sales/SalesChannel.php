<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Sales\Exception\EmptyChannelName;
use App\Domain\Sales\Exception\MainChannelKept;
use App\Domain\Sales\Exception\NotASupply;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Shared\Money;
use App\Domain\Shared\OptionalText;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'sales_channel')]
#[ORM\UniqueConstraint(name: 'sales_channel_workspace_name', columns: ['workspace_id', 'name'])]
#[ORM\UniqueConstraint(name: 'sales_channel_workspace_service', columns: ['workspace_id', 'service'])]
class SalesChannel
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(length: 16, enumType: ChannelKind::class, options: ['default' => 'online'])]
    private ChannelKind $kind;

    #[ORM\Column(options: ['default' => false])]
    private bool $main = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'sales_channel_supply')]
    #[ORM\JoinColumn(name: 'channel_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'product_id', onDelete: 'CASCADE')]
    private Collection $supplies;

    /** @var Collection<int, ChannelCost> */
    #[ORM\OneToMany(targetEntity: ChannelCost::class, mappedBy: 'channel', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $costs;

    private function __construct(Workspace $workspace, string $name, ChannelKind $kind, ?string $service)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->createdAt = new \DateTimeImmutable();
        $this->supplies = new ArrayCollection();
        $this->costs = new ArrayCollection();
        $this->rename($name);
        $this->kind = $kind;
        $this->linkTo($service);
    }

    public static function open(Workspace $workspace, string $name, ChannelKind $kind = ChannelKind::Online, ?string $service = null): self
    {
        return new self($workspace, $name, $kind, $service);
    }

    public static function main(Workspace $workspace, string $name): self
    {
        $channel = new self($workspace, $name, ChannelKind::Market, null);
        $channel->main = true;

        return $channel;
    }

    public function changeKind(ChannelKind $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @param list<Product> $supplies
     */
    public function offerSupplies(array $supplies): void
    {
        foreach ($supplies as $supply) {
            if (!$supply->isSupply()) {
                throw new NotASupply($supply->displayName());
            }
        }

        $this->supplies->clear();
        foreach ($supplies as $supply) {
            if (!$this->supplies->contains($supply)) {
                $this->supplies->add($supply);
            }
        }
    }

    public function offers(Ulid $supplyId): bool
    {
        return $this->supplies->exists(static fn (int $key, Product $supply): bool => $supply->id()->equals($supplyId));
    }

    /**
     * @return list<Product>
     */
    public function supplies(): array
    {
        return array_values($this->supplies->toArray());
    }

    public function addCost(string $label, ChannelCostKind $kind, int $amount): ChannelCost
    {
        $cost = new ChannelCost($this, $label, $kind, $amount);
        $this->costs->add($cost);

        return $cost;
    }

    public function reviseCost(Ulid $costId, string $label, ChannelCostKind $kind, int $amount): void
    {
        $this->cost($costId)->revise($label, $kind, $amount);
    }

    public function removeCost(Ulid $costId): void
    {
        $this->costs->removeElement($this->cost($costId));
    }

    /**
     * @return list<ChannelCost>
     */
    public function costs(): array
    {
        return array_values($this->costs->toArray());
    }

    /**
     * @return list<OrderCharge>
     */
    public function chargesOn(Money $orderTotal): array
    {
        return array_map(static fn (ChannelCost $cost): OrderCharge => $cost->on($orderTotal), $this->costs());
    }

    private function cost(Ulid $costId): ChannelCost
    {
        return $this->costs->findFirst(static fn (int $key, ChannelCost $cost): bool => $cost->id()->equals($costId))
            ?? throw new NotFound('channel_cost', (string) $costId);
    }

    public function acceptsOrderWithoutEvent(): bool
    {
        return ChannelKind::Market !== $this->kind;
    }

    public function isMain(): bool
    {
        return $this->main;
    }

    public function assertRemovable(): void
    {
        if ($this->main) {
            throw new MainChannelKept($this->name);
        }
    }

    public function kind(): ChannelKind
    {
        return $this->kind;
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyChannelName();
        }

        $this->name = $name;
    }

    public function linkTo(?string $service): void
    {
        $this->service = OptionalText::of($service);
    }

    public function isNamed(string $name): bool
    {
        return mb_strtolower(trim($name)) === mb_strtolower($this->name);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function service(): ?string
    {
        return $this->service;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
