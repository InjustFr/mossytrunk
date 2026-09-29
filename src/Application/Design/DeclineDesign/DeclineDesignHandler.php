<?php

declare(strict_types=1);

namespace App\Application\Design\DeclineDesign;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeclineDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private GabaritRepository $gabarits,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId, string $gabaritId): Ulid
    {
        $declination = $this->designs->get(Ulid::fromString($designId))->decline($this->gabarits->get(Ulid::fromString($gabaritId)));
        $this->transaction->commit();

        return $declination->id();
    }
}
