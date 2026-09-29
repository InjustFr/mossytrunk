<?php

declare(strict_types=1);

namespace App\Application\Design\DesignProduct;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use App\Domain\Design\InvalidDesign;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DesignProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function create(string $productId, string $gabaritId, ?string $collectionId = null): Ulid
    {
        $product = $this->unDesigned($productId);
        $design = Design::fromProduct(
            $this->workspace->current(),
            $product,
            $this->gabarits->get(Ulid::fromString($gabaritId)),
            null === $collectionId ? null : $this->collections->get(Ulid::fromString($collectionId)),
            $this->clock->now(),
        );
        $this->designs->add($design);
        $this->transaction->commit();

        return $design->id();
    }

    public function attach(string $productId, string $designId, string $gabaritId): Ulid
    {
        $product = $this->unDesigned($productId);
        $design = $this->designs->get(Ulid::fromString($designId));
        $design->adopt($product, $this->gabarits->get(Ulid::fromString($gabaritId)), $this->clock->now());
        $this->transaction->commit();

        return $design->id();
    }

    private function unDesigned(string $productId): Product
    {
        $product = $this->products->get(Ulid::fromString($productId));
        $existing = $this->designs->findByProduct($product->id());
        if (null !== $existing) {
            throw InvalidDesign::productAlreadyDesigned($product->displayName(), $existing->name());
        }

        return $product;
    }
}
