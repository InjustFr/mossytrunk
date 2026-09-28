<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'password_token')]
class PasswordToken
{
    public const int SELECTOR_LENGTH = 24;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: self::SELECTOR_LENGTH, unique: true)]
    private string $selector;

    #[ORM\Column(length: 64)]
    private string $verifierHash;

    #[ORM\Column(length: 20, enumType: PasswordTokenPurpose::class)]
    private PasswordTokenPurpose $purpose;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    private function __construct(Ulid $id, User $user, PasswordTokenPurpose $purpose, string $selector, string $verifierHash, \DateTimeImmutable $expiresAt)
    {
        $this->id = $id;
        $this->user = $user;
        $this->purpose = $purpose;
        $this->selector = $selector;
        $this->verifierHash = $verifierHash;
        $this->expiresAt = $expiresAt;
    }

    public static function issue(User $user, PasswordTokenPurpose $purpose, string $selector, string $verifier, \DateTimeImmutable $now): self
    {
        if (self::SELECTOR_LENGTH !== \strlen($selector) || '' === $verifier) {
            throw InvalidPasswordToken::invalid();
        }

        return new self(new Ulid(), $user, $purpose, $selector, hash('sha256', $verifier), $now->add($purpose->lifetime()));
    }

    public function verify(string $verifier, \DateTimeImmutable $now): void
    {
        if (!hash_equals($this->verifierHash, hash('sha256', $verifier))) {
            throw InvalidPasswordToken::invalid();
        }
        if (null !== $this->usedAt) {
            throw InvalidPasswordToken::alreadyUsed();
        }
        if ($now >= $this->expiresAt) {
            throw InvalidPasswordToken::expired();
        }
    }

    public function consume(string $verifier, \DateTimeImmutable $now): void
    {
        $this->verify($verifier, $now);
        $this->usedAt = $now;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function purpose(): PasswordTokenPurpose
    {
        return $this->purpose;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
