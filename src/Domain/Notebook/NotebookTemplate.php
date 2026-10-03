<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use App\Domain\Identity\Workspace;
use App\Domain\Notebook\Exception\DuplicateAbbreviation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'notebook_template')]
#[ORM\UniqueConstraint(name: 'notebook_template_workspace', columns: ['workspace_id'])]
class NotebookTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 16, enumType: SaleSeparation::class)]
    private SaleSeparation $separation;

    /** @var list<array{short: string, full: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $abbreviations = [];

    private function __construct(Workspace $workspace)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->separation = SaleSeparation::Numbered;
    }

    public static function standard(Workspace $workspace): self
    {
        return new self($workspace);
    }

    /**
     * @param list<Abbreviation> $abbreviations
     */
    public function configure(SaleSeparation $separation, array $abbreviations): void
    {
        $seen = [];
        foreach ($abbreviations as $abbreviation) {
            if (isset($seen[$abbreviation->key()])) {
                throw new DuplicateAbbreviation($abbreviation->short);
            }
            $seen[$abbreviation->key()] = true;
        }

        $this->separation = $separation;
        $this->abbreviations = array_map(static fn (Abbreviation $abbreviation): array => ['short' => $abbreviation->short, 'full' => $abbreviation->full], $abbreviations);
    }

    /**
     * @param list<string> $pages
     *
     * @return list<WrittenSale>
     */
    public function sales(array $pages): array
    {
        $sales = [];
        foreach ($pages as $index => $text) {
            foreach ($this->separation->boundaries()->split(explode("\n", str_replace("\r", '', $text))) as $lines) {
                $items = ItemParser::items($lines);
                if ([] !== $items) {
                    $sales[] = new WrittenSale($index + 1, $items);
                }
            }
        }

        return $sales;
    }

    public function expand(string $written): string
    {
        return array_reduce($this->abbreviations(), static fn (string $text, Abbreviation $abbreviation): string => $abbreviation->expand($text), $written);
    }

    public function separation(): SaleSeparation
    {
        return $this->separation;
    }

    /**
     * @return list<Abbreviation>
     */
    public function abbreviations(): array
    {
        return array_map(static fn (array $abbreviation): Abbreviation => new Abbreviation($abbreviation['short'], $abbreviation['full']), $this->abbreviations);
    }
}
