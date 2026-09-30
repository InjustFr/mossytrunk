<?php

declare(strict_types=1);

namespace App\Application\Design\DesignProduct;

use App\Application\Design\UndesignedProducts;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DesignProductHandler
{
    public function __construct(
        private UndesignedProducts $products,
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, string $gabaritId, ?string $collectionId = null): Ulid
    {
        $design = Design::fromProduct(
            $this->workspace->current(),
            $this->products->get($productId),
            $this->gabarits->get(Ulid::fromString($gabaritId)),
            null === $collectionId ? null : $this->collections->get(Ulid::fromString($collectionId)),
            $this->clock->now(),
        );
        $this->designs->add($design);
        $this->transaction->commit();

        return $design->id();
    }
}
