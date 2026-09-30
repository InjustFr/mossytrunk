<?php

declare(strict_types=1);

namespace App\Application\Design\SaveGabarit;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Exception\GabaritAlreadyExists;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class SaveGabaritHandler
{
    public function __construct(
        private GabaritRepository $gabarits,
        private ProductTypeRepository $types,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(SaveGabarit $command): Gabarit
    {
        $type = null === $command->typeId ? null : $this->types->get(Ulid::fromString($command->typeId));
        $namesake = $this->gabarits->findByName($command->name);

        if (null === $command->gabaritId) {
            if (null !== $namesake) {
                throw new GabaritAlreadyExists($namesake->name());
            }
            $gabarit = Gabarit::create($this->workspace->current(), $command->name, $type, Money::cents($command->sellingPriceCents), $command->variants, $command->adaptations);
            $this->gabarits->add($gabarit);
        } else {
            $gabarit = $this->gabarits->get(Ulid::fromString($command->gabaritId));
            if (null !== $namesake && $namesake !== $gabarit) {
                throw new GabaritAlreadyExists($namesake->name());
            }
            $gabarit->describe($command->name, $type, Money::cents($command->sellingPriceCents), $command->variants, $command->adaptations);
        }

        $this->transaction->commit();

        return $gabarit;
    }
}
