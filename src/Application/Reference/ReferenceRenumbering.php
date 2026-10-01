<?php

declare(strict_types=1);

namespace App\Application\Reference;

use App\Application\AtomicChange;
use App\Application\Transaction;
use App\Domain\Reference\Referenced;
use App\Domain\Reference\ReferenceFormat;

final readonly class ReferenceRenumbering
{
    private const string PROVISIONAL_PREFIX = '~';

    public function __construct(
        private ReferenceBook $book,
        private AtomicChange $atomically,
        private Transaction $transaction,
    ) {
    }

    public function renumber(ReferenceFormat $format): int
    {
        $format->restartNumbering();
        $renamed = $this->newReferences($format);

        $this->atomically->apply(function () use ($renamed): void {
            foreach ($renamed as [$item]) {
                $item->changeReference(self::PROVISIONAL_PREFIX.bin2hex(random_bytes(12)));
            }
            $this->transaction->commit();
            foreach ($renamed as [$item, $reference]) {
                $item->changeReference($reference);
            }
            $this->transaction->commit();
        });

        return \count($renamed);
    }

    /**
     * @return list<array{Referenced, string}>
     */
    private function newReferences(ReferenceFormat $format): array
    {
        $issued = [];
        $renamed = [];
        foreach ($this->book->of($format->kind())->oldestFirst() as $item) {
            $reference = $format->issue($item->referenceSubject(), static function (string $candidate) use (&$issued): bool {
                return isset($issued[$candidate]);
            });
            $issued[$reference] = true;
            if ($reference !== $item->reference()) {
                $renamed[] = [$item, $reference];
            }
        }

        return $renamed;
    }
}
