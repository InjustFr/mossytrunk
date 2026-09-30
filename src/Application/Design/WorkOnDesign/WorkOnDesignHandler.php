<?php

declare(strict_types=1);

namespace App\Application\Design\WorkOnDesign;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class WorkOnDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId, bool $current): void
    {
        $this->designs->get(Ulid::fromString($designId))->workOn($current);
        $this->transaction->commit();
    }
}
