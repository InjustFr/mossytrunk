<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Event\Event;
use App\Domain\Event\ExpenseShare;

final readonly class ExpenseShareRow
{
    /**
     * @return array{id: string, label: string, amount: int, fullAmount: int, sharedBy: int, sharedOverEvents: int|null, sharedUntil: string|null, own: bool, originId: string, originName: string}
     */
    public static function of(ExpenseShare $share, Event $event): array
    {
        $expense = $share->expense;
        $origin = $expense->event();

        return [
            'id' => (string) $expense->id(),
            'label' => $expense->label(),
            'amount' => $share->amount->amount(),
            'fullAmount' => $expense->amount()->amount(),
            'sharedBy' => $share->sharedBy,
            'sharedOverEvents' => $expense->spread()->events,
            'sharedUntil' => $expense->spread()->until?->format('Y-m-d'),
            'own' => $origin === $event,
            'originId' => (string) $origin->id(),
            'originName' => $origin->name(),
        ];
    }
}
