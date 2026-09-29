<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Accounting\DeclarationPeriodicity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'workspace')]
class Workspace
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $sumUpMerchantCode = null;

    #[ORM\Column(length: 16, enumType: DeclarationPeriodicity::class, options: ['default' => 'monthly'])]
    private DeclarationPeriodicity $declarationPeriodicity = DeclarationPeriodicity::Monthly;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $etsyKeystring = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $etsyShopId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etsyShopName = null;

    #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $etsyTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(Ulid $id, string $name)
    {
        $this->id = $id;
        $this->createdAt = new \DateTimeImmutable();
        $this->rename($name);
    }

    public static function create(string $name): self
    {
        return new self(new Ulid(), $name);
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidAccount::emptyWorkspaceName();
        }

        $this->name = $name;
    }

    public function configureSumUp(string $merchantCode): void
    {
        $merchantCode = strtoupper(trim($merchantCode));
        if (1 !== preg_match('/^[A-Z0-9]{1,32}$/', $merchantCode)) {
            throw InvalidAccount::invalidSumUpMerchantCode($merchantCode);
        }

        $this->sumUpMerchantCode = $merchantCode;
    }

    public function configureEtsyApp(string $keystring): bool
    {
        $keystring = trim($keystring);
        if (1 !== preg_match('/^[A-Za-z0-9]{1,64}$/', $keystring)) {
            throw InvalidAccount::invalidEtsyKeystring();
        }
        $changed = $keystring !== $this->etsyKeystring;
        $this->etsyKeystring = $keystring;

        return $changed;
    }

    public function etsyKeystring(): ?string
    {
        return $this->etsyKeystring;
    }

    public function connectEtsy(string $shopId, string $shopName, \DateTimeImmutable $tokenExpiresAt): void
    {
        $this->etsyShopId = $shopId;
        $this->etsyShopName = $shopName;
        $this->etsyTokenExpiresAt = $tokenExpiresAt;
    }

    public function renewEtsyToken(\DateTimeImmutable $tokenExpiresAt): void
    {
        $this->etsyTokenExpiresAt = $tokenExpiresAt;
    }

    public function disconnectEtsy(): void
    {
        $this->etsyShopId = null;
        $this->etsyShopName = null;
        $this->etsyTokenExpiresAt = null;
    }

    public function etsyShopId(): ?string
    {
        return $this->etsyShopId;
    }

    public function etsyShopName(): ?string
    {
        return $this->etsyShopName;
    }

    public function etsyTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->etsyTokenExpiresAt;
    }

    public function declareEvery(DeclarationPeriodicity $periodicity): void
    {
        $this->declarationPeriodicity = $periodicity;
    }

    public function declarationPeriodicity(): DeclarationPeriodicity
    {
        return $this->declarationPeriodicity;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sumUpMerchantCode(): ?string
    {
        return $this->sumUpMerchantCode;
    }
}
