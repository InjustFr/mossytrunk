<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Identity\Workspace;
use App\Domain\Sales\Exception\EmptyChannelName;
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

    private function __construct(Workspace $workspace, string $name, ChannelKind $kind, ?string $service)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->createdAt = new \DateTimeImmutable();
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

    public function acceptsOrderWithoutEvent(): bool
    {
        return ChannelKind::Market !== $this->kind;
    }

    public function isMain(): bool
    {
        return $this->main;
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
        $this->service = null === $service || '' === trim($service) ? null : trim($service);
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
