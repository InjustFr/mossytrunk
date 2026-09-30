<?php

declare(strict_types=1);

namespace App\Application\Design\ValidateDesign;

use App\Application\Design\DesignProduction;
use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ValidateDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignProduction $production,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId): int
    {
        $created = $this->production->produce($this->designs->get(Ulid::fromString($designId)));
        $this->transaction->commit();

        return $created;
    }
}
