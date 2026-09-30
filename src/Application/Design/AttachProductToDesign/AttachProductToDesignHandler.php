<?php

declare(strict_types=1);

namespace App\Application\Design\AttachProductToDesign;

use App\Application\Design\UndesignedProducts;
use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class AttachProductToDesignHandler
{
    public function __construct(
        private UndesignedProducts $products,
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, string $designId, string $gabaritId): Ulid
    {
        $product = $this->products->get($productId);
        $design = $this->designs->get(Ulid::fromString($designId));
        $design->adopt($product, $this->gabarits->get(Ulid::fromString($gabaritId)), $this->clock->now());
        $this->transaction->commit();

        return $design->id();
    }
}
