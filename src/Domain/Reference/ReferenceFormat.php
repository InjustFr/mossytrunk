<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Identity\Workspace;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'reference_format')]
#[ORM\UniqueConstraint(name: 'reference_format_workspace_kind', columns: ['workspace_id', 'kind'])]
class ReferenceFormat
{
    private const int RENDERINGS_BEFORE_SUFFIX = 100;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 32, enumType: ReferenceKind::class)]
    private ReferenceKind $kind;

    #[ORM\Column(length: 255)]
    private string $template;

    #[ORM\Column]
    private int $nextNumber = 1;

    private function __construct(Workspace $workspace, ReferenceKind $kind)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->kind = $kind;
        $this->template = $kind->defaultTemplate();
    }

    public static function standard(Workspace $workspace, ReferenceKind $kind): self
    {
        return new self($workspace, $kind);
    }

    public function change(string $template): void
    {
        $this->template = ReferenceTemplate::of($this->kind, $template)->value;
    }

    public function restartNumbering(): void
    {
        $this->nextNumber = 1;
    }

    /**
     * @param callable(string): bool $taken
     */
    public function issue(ReferenceSubject $subject, callable $taken): string
    {
        $template = $this->template();
        for ($attempt = 1;; ++$attempt) {
            $rendered = $template->render($subject, $template->numbers() ? $this->nextNumber++ : $this->nextNumber);
            $candidate = $template->varies() && $attempt <= self::RENDERINGS_BEFORE_SUFFIX ? $rendered : self::suffixed($rendered, $attempt);
            if (!$taken($candidate)) {
                return $candidate;
            }
        }
    }

    public function template(): ReferenceTemplate
    {
        return ReferenceTemplate::of($this->kind, $this->template);
    }

    public function kind(): ReferenceKind
    {
        return $this->kind;
    }

    public function nextNumber(): int
    {
        return $this->nextNumber;
    }

    private static function suffixed(string $reference, int $attempt): string
    {
        return 1 === $attempt ? $reference : \sprintf('%s-%d', $reference, $attempt);
    }
}
