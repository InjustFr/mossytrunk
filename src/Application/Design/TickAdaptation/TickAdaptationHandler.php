<?php

declare(strict_types=1);

namespace App\Application\Design\TickAdaptation;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class TickAdaptationHandler
{
    public function __construct(
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId, string $declinationId, string $adaptation, bool $done): void
    {
        $this->designs->get(Ulid::fromString($designId))->tick(Ulid::fromString($declinationId), $adaptation, $done);
        $this->transaction->commit();
    }
}
