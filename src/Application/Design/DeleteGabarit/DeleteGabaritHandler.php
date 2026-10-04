<?php

declare(strict_types=1);

namespace App\Application\Design\DeleteGabarit;

use App\Application\Transaction;
use App\Domain\Design\Design;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\Exception\GabaritInUse;
use App\Domain\Design\GabaritRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteGabaritHandler
{
    public function __construct(
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $gabaritId): void
    {
        $gabarit = $this->gabarits->get(Ulid::fromString($gabaritId));

        $declined = array_filter($this->designs->all(), static fn (Design $design): bool => $design->isDeclinedOn($gabarit));
        if ([] !== $declined) {
            throw new GabaritInUse($gabarit->name(), \count($declined));
        }

        $this->gabarits->remove($gabarit);
        $this->transaction->commit();
    }
}
