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

    #[ORM\Column(length: 16, enumType: DeclarationPeriodicity::class, options: ['default' => 'monthly'])]
    private DeclarationPeriodicity $declarationPeriodicity = DeclarationPeriodicity::Monthly;

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
}
