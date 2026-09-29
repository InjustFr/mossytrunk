<?php

declare(strict_types=1);

namespace App\Application\Design\AdjustDeclination;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class AdjustDeclinationHandler
{
    public function __construct(
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AdjustDeclination $command): void
    {
        $this->designs->get(Ulid::fromString($command->designId))->adjust(
            Ulid::fromString($command->declinationId),
            $command->productName,
            Money::cents($command->sellingPriceCents),
            Money::cents($command->buyingPriceCents),
            $command->variants,
        );
        $this->transaction->commit();
    }
}
