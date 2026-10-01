<?php

declare(strict_types=1);

namespace App\Application\Reference\ChangeReferenceFormat;

use App\Application\Reference\ReferenceRenumbering;
use App\Application\Transaction;
use App\Domain\Reference\ReferenceFormatRepository;

final readonly class ChangeReferenceFormatHandler
{
    public function __construct(
        private ReferenceFormatRepository $formats,
        private ReferenceRenumbering $renumbering,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ChangeReferenceFormat $command): int
    {
        $format = $this->formats->of($command->kind);
        $format->change($command->template);
        if (!$command->applyToExisting) {
            $this->transaction->commit();

            return 0;
        }

        return $this->renumbering->renumber($format);
    }
}
