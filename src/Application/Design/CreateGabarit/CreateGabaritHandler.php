<?php

declare(strict_types=1);

namespace App\Application\Design\CreateGabarit;

use App\Application\Design\GabaritAvailability;
use App\Application\Product\ProductTypeChoice;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Shared\Money;

final readonly class CreateGabaritHandler
{
    public function __construct(
        private GabaritRepository $gabarits,
        private GabaritAvailability $availability,
        private ProductTypeChoice $types,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(CreateGabarit $command): Gabarit
    {
        $type = $this->types->of($command->typeId);
        $this->availability->assertNameFree($command->name);

        $gabarit = Gabarit::create($this->workspace->current(), $command->name, $type, Money::cents($command->sellingPriceCents), $command->variants, $command->adaptations);
        $this->gabarits->add($gabarit);
        $this->transaction->commit();

        return $gabarit;
    }
}
