<?php

declare(strict_types=1);

namespace App\Application\Reference\ListReferenceFormats;

use App\Application\Reference\ReferenceBook;
use App\Application\Reference\ReferenceExample;
use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Reference\ReferenceToken;

final readonly class ListReferenceFormatsHandler
{
    public function __construct(
        private ReferenceFormatRepository $formats,
        private ReferenceBook $book,
        private ReferenceExample $example,
    ) {
    }

    /**
     * @return list<ReferenceFormatView>
     */
    public function __invoke(): array
    {
        return array_map(function (ReferenceKind $kind): ReferenceFormatView {
            $format = $this->formats->of($kind);

            return new ReferenceFormatView(
                $kind->value,
                $format->template()->value,
                $kind->defaultTemplate(),
                array_map(static fn (ReferenceToken $token): string => $token->value, $kind->tokens()),
                $this->example->of($format->template(), $format->nextNumber()),
                $this->book->of($kind)->count(),
            );
        }, ReferenceKind::cases());
    }
}
