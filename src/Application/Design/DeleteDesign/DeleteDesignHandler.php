<?php

declare(strict_types=1);

namespace App\Application\Design\DeleteDesign;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\InvalidDesign;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId): void
    {
        $design = $this->designs->get(Ulid::fromString($designId));
        if ($design->isValidated()) {
            throw InvalidDesign::alreadyValidated($design->name());
        }
        $this->designs->remove($design);
        $this->transaction->commit();
    }
}
