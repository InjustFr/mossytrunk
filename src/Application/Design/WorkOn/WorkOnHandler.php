<?php

declare(strict_types=1);

namespace App\Application\Design\WorkOn;

use App\Application\Transaction;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class WorkOnHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
        private Transaction $transaction,
    ) {
    }

    public function design(string $designId, bool $current): void
    {
        $this->designs->get(Ulid::fromString($designId))->workOn($current);
        $this->transaction->commit();
    }

    public function collection(string $collectionId, bool $current): void
    {
        $this->collections->get(Ulid::fromString($collectionId))->workOn($current);
        $this->transaction->commit();
    }
}
