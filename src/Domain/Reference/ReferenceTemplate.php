<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Reference\Exception\EmptyReferenceTemplate;
use App\Domain\Reference\Exception\ForbiddenReferenceCharacter;
use App\Domain\Reference\Exception\ReferenceTemplateTooLong;

final readonly class ReferenceTemplate
{
    public const int MAX_LENGTH = 56;

    private const string PLACEHOLDER = '/(\{[^{}]*\})/u';
    private const string FORBIDDEN_CHARACTER = '/[^A-Za-z0-9_.\/#-]/u';

    /**
     * @param list<string|ReferencePlaceholder> $parts
     */
    private function __construct(public string $value, private array $parts)
    {
    }

    public static function of(ReferenceKind $kind, string $template): self
    {
        $template = trim($template);
        if ('' === $template) {
            throw new EmptyReferenceTemplate();
        }

        $parts = [];
        foreach (preg_split(self::PLACEHOLDER, $template, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) ?: [] as $piece) {
            $parts[] = 1 === preg_match(self::PLACEHOLDER, $piece) ? ReferencePlaceholder::of($kind, substr($piece, 1, -1)) : self::literal($piece);
        }

        $reference = new self($template, $parts);
        if ($reference->maxLength() > self::MAX_LENGTH) {
            throw new ReferenceTemplateTooLong(self::MAX_LENGTH);
        }

        return $reference;
    }

    public function render(ReferenceSubject $subject, int $number): string
    {
        return implode('', array_map(
            static fn (string|ReferencePlaceholder $part): string => \is_string($part) ? $part : $part->render($subject, $number),
            $this->parts,
        ));
    }

    public function numbers(): bool
    {
        return $this->uses(ReferenceToken::Number);
    }

    public function varies(): bool
    {
        return $this->numbers() || $this->uses(ReferenceToken::Random);
    }

    private function uses(ReferenceToken $token): bool
    {
        return array_any($this->parts, static fn (string|ReferencePlaceholder $part): bool => $part instanceof ReferencePlaceholder && $token === $part->token);
    }

    private function maxLength(): int
    {
        return array_sum(array_map(
            static fn (string|ReferencePlaceholder $part): int => \is_string($part) ? \strlen($part) : $part->width(),
            $this->parts,
        ));
    }

    private static function literal(string $piece): string
    {
        if (1 === preg_match(self::FORBIDDEN_CHARACTER, $piece, $match)) {
            throw new ForbiddenReferenceCharacter($match[0]);
        }

        return $piece;
    }
}
