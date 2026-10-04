<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Identity\Workspace;
use App\Domain\Purchasing\Exception\EmptySupplierName;
use App\Domain\Shared\OptionalText;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'supplier')]
#[ORM\UniqueConstraint(name: 'supplier_workspace_name', columns: ['workspace_id', 'name'])]
class Supplier
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    private function __construct(Workspace $workspace, string $name, ?string $contact, ?string $notes)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->describe($name, $contact, $notes);
    }

    public static function create(Workspace $workspace, string $name, ?string $contact = null, ?string $notes = null): self
    {
        return new self($workspace, $name, $contact, $notes);
    }

    public function describe(string $name, ?string $contact, ?string $notes): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptySupplierName();
        }

        $this->name = $name;
        $this->contact = OptionalText::of($contact);
        $this->notes = OptionalText::of($notes);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function contact(): ?string
    {
        return $this->contact;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
