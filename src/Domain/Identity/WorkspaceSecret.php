<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_secret')]
#[ORM\UniqueConstraint(name: 'workspace_secret_workspace_name', columns: ['workspace_id', 'name'])]
class WorkspaceSecret
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 50, enumType: SecretName::class)]
    private SecretName $name;

    #[ORM\Column(type: 'text')]
    private string $ciphertext;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(Workspace $workspace, SecretName $name, string $ciphertext)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->name = $name;
        $this->replace($ciphertext);
    }

    public static function store(Workspace $workspace, SecretName $name, string $ciphertext): self
    {
        return new self($workspace, $name, $ciphertext);
    }

    public function replace(string $ciphertext): void
    {
        $this->ciphertext = $ciphertext;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function name(): SecretName
    {
        return $this->name;
    }

    public function ciphertext(): string
    {
        return $this->ciphertext;
    }
}
