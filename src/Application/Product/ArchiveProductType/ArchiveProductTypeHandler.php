<?php

declare(strict_types=1);

namespace App\Application\Product\ArchiveProductType;

use App\Application\Transaction;
use App\Domain\Product\ProductTypeRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ArchiveProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $typeId): void
    {
        $this->types->get(Ulid::fromString($typeId))->archive($this->clock->now());
        $this->transaction->commit();
    }
}
