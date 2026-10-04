<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Design\Exception\EmptyDesignName;
use App\Domain\Identity\Workspace;
use App\Domain\Shared\OptionalText;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'design_collection')]
class DesignCollection
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $current = true;

    private function __construct(Workspace $workspace, string $name, ?string $description)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->describe($name, $description);
    }

    public static function start(Workspace $workspace, string $name, ?string $description = null): self
    {
        return new self($workspace, $name, $description);
    }

    public function describe(string $name, ?string $description): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyDesignName('collection');
        }

        $this->name = $name;
        $this->description = OptionalText::of($description);
    }

    public function workOn(bool $current): void
    {
        $this->current = $current;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
