<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Reference\Exception\InvalidReferenceTokenSize;
use App\Domain\Reference\Exception\ReferenceTokenWithoutSize;
use App\Domain\Reference\Exception\UnknownReferenceToken;

final readonly class ReferencePlaceholder
{
    private function __construct(public ReferenceToken $token, private int $size)
    {
    }

    public static function of(ReferenceKind $kind, string $placeholder): self
    {
        $name = strstr($placeholder, ':', true);
        $size = false === $name ? null : substr($placeholder, \strlen($name) + 1);
        $token = ReferenceToken::tryFrom(false === $name ? $placeholder : $name);
        if (null === $token || !$kind->offers($token)) {
            throw new UnknownReferenceToken($placeholder);
        }

        return new self($token, null === $size ? $token->defaultSize() ?? 1 : self::size($token, $size));
    }

    public function width(): int
    {
        return $this->token->width($this->size);
    }

    public function render(ReferenceSubject $subject, int $number): string
    {
        return $this->token->render($subject, $this->size, $number);
    }

    private static function size(ReferenceToken $token, string $size): int
    {
        if (null === $token->defaultSize()) {
            throw new ReferenceTokenWithoutSize($token->value);
        }
        if (!ctype_digit($size) || (int) $size < $token->minSize() || (int) $size > $token->maxSize()) {
            throw new InvalidReferenceTokenSize($token->value, $token->minSize(), $token->maxSize());
        }

        return (int) $size;
    }
}
