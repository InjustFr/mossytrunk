<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(Ulid $id, string $email, Workspace $workspace)
    {
        $this->id = $id;
        $this->createdAt = new \DateTimeImmutable();
        $this->email = self::normalizeEmail($email);
        $this->workspace = $workspace;
    }

    public static function invite(string $email, Workspace $workspace): self
    {
        return new self(new Ulid(), $email, $workspace);
    }

    public static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidAccount::invalidEmail($email);
        }

        return $email;
    }

    public function changePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function hasPassword(): bool
    {
        return null !== $this->passwordHash;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
