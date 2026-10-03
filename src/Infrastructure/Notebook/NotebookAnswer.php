<?php

declare(strict_types=1);

namespace App\Infrastructure\Notebook;

use App\Application\Notebook\NotebookCatalogue;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Infrastructure\Http\Json;

final class NotebookAnswer
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $nullableString = ['anyOf' => [['type' => 'string'], ['type' => 'null']]];

        return [
            'type' => 'object',
            'properties' => [
                'entries' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'page' => ['type' => 'integer'],
                            'lines' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'written' => ['type' => 'string'],
                                        'quantity' => ['type' => 'integer'],
                                        'productId' => $nullableString,
                                        'variant' => $nullableString,
                                        'typeId' => $nullableString,
                                    ],
                                    'required' => ['written', 'quantity', 'productId', 'variant', 'typeId'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['page', 'lines'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['entries'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param array<string, mixed> $answer
     *
     * @return list<NotebookEntry>
     */
    public static function entries(array $answer, NotebookCatalogue $catalogue): array
    {
        $entries = [];
        foreach (Json::objects($answer['entries'] ?? []) as $entry) {
            $lines = array_map(
                static fn (array $line): NotebookLine => $catalogue->line(
                    trim(Json::string($line['written'] ?? '')),
                    max(1, (int) Json::number($line['quantity'] ?? 1)),
                    self::nullableString($line['productId'] ?? null),
                    self::nullableString($line['variant'] ?? null),
                    self::nullableString($line['typeId'] ?? null),
                ),
                Json::objects($entry['lines'] ?? []),
            );
            if ([] !== $lines) {
                $entries[] = new NotebookEntry(max(1, (int) Json::number($entry['page'] ?? 1)), $lines);
            }
        }

        return $entries;
    }

    private static function nullableString(mixed $value): ?string
    {
        return \is_string($value) && '' !== trim($value) ? trim($value) : null;
    }
}
