<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Domain\Design\Declination;
use App\Domain\Design\Design;

final readonly class DesignView
{
    /**
     * @param array{id: string, name: string}|null                                                                                                                                                                                                                                                                     $collection
     * @param list<array{id: string, gabarit: array{id: string, name: string, typeId: string, typeName: string, prefixesNames: bool}, productName: string, displayName: string, sellingPrice: int, variants: list<string>, adaptations: list<string>, doneAdaptations: list<string>, ready: bool, productId: ?string}> $declinations
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $notes,
        public string $status,
        public bool $current,
        public ?array $collection,
        public ?string $validatedAt,
        public int $adaptationsDone,
        public int $adaptationsTotal,
        public array $declinations,
    ) {
    }

    public static function of(Design $design): self
    {
        $declinations = $design->declinations();

        return new self(
            (string) $design->id(),
            $design->name(),
            $design->notes(),
            $design->status()->value,
            $design->isCurrent(),
            null === $design->collection() ? null : ['id' => (string) $design->collection()->id(), 'name' => $design->collection()->name()],
            $design->validatedAt()?->format(\DateTimeInterface::ATOM),
            array_sum(array_map(static fn (Declination $declination): int => \count($declination->adaptations()) - \count($declination->pendingAdaptations()), $declinations)),
            array_sum(array_map(static fn (Declination $declination): int => \count($declination->adaptations()), $declinations)),
            array_map(static fn (Declination $declination): array => [
                'id' => (string) $declination->id(),
                'gabarit' => [
                    'id' => (string) $declination->gabarit()->id(),
                    'name' => $declination->gabarit()->name(),
                    'typeId' => (string) $declination->gabarit()->type()->id(),
                    'typeName' => $declination->gabarit()->type()->name(),
                    'prefixesNames' => $declination->gabarit()->type()->prefixesNames(),
                ],
                'productName' => $declination->productName(),
                'displayName' => $declination->displayName(),
                'sellingPrice' => $declination->sellingPrice()->amount(),
                'variants' => $declination->variants(),
                'adaptations' => $declination->adaptations(),
                'doneAdaptations' => $declination->doneAdaptations(),
                'ready' => [] === $declination->pendingAdaptations(),
                'productId' => null === $declination->productId() ? null : (string) $declination->productId(),
            ], $declinations),
        );
    }
}
