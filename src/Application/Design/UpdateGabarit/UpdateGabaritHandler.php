<?php

declare(strict_types=1);

namespace App\Application\Design\UpdateGabarit;

use App\Application\Design\GabaritAvailability;
use App\Application\Product\ProductTypeChoice;
use App\Application\Transaction;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateGabaritHandler
{
    public function __construct(
        private GabaritRepository $gabarits,
        private GabaritAvailability $availability,
        private ProductTypeChoice $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateGabarit $command): Gabarit
    {
        $type = $this->types->of($command->typeId);
        $gabarit = $this->gabarits->get(Ulid::fromString($command->gabaritId));
        $this->availability->assertNameFree($command->name, $gabarit);

        $gabarit->describe($command->name, $type, Money::cents($command->sellingPriceCents), $command->variants, $command->adaptations);
        $this->transaction->commit();

        return $gabarit;
    }
}
