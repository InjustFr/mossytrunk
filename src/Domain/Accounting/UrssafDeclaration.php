<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

use App\Domain\Identity\Workspace;
use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'urssaf_declaration')]
#[ORM\UniqueConstraint(name: 'urssaf_declaration_workspace_period', columns: ['workspace_id', 'period'])]
class UrssafDeclaration
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 16)]
    private string $period;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'turnover_')]
    private Money $turnover;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $declaredAt;

    private function __construct(Workspace $workspace, DeclarationPeriod $period, Money $turnover, \DateTimeImmutable $declaredAt)
    {
        if (!$period->isOverOn($declaredAt)) {
            throw InvalidDeclaration::periodNotOver($period->key());
        }

        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->period = $period->key();
        $this->turnover = $turnover;
        $this->declaredAt = $declaredAt;
    }

    public static function record(Workspace $workspace, DeclarationPeriod $period, Money $turnover, \DateTimeImmutable $declaredAt): self
    {
        return new self($workspace, $period, $turnover, $declaredAt);
    }

    public function period(): string
    {
        return $this->period;
    }

    public function turnover(): Money
    {
        return $this->turnover;
    }

    public function declaredAt(): \DateTimeImmutable
    {
        return $this->declaredAt;
    }
}
