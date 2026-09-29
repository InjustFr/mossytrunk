<?php

declare(strict_types=1);

namespace App\Application\Design\ValidateDesign;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ValidateDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId): int
    {
        $created = $this->produce($this->designs->get(Ulid::fromString($designId)));
        $this->transaction->commit();

        return $created;
    }

    public function collection(string $collectionId): int
    {
        $created = 0;
        foreach ($this->designs->inCollection(Ulid::fromString($collectionId)) as $design) {
            if (!$design->isValidated()) {
                $created += $this->produce($design);
            }
        }
        $this->transaction->commit();

        return $created;
    }

    private function produce(Design $design): int
    {
        $declinations = $design->validate($this->clock->now());
        foreach ($declinations as $declination) {
            $type = $declination->gabarit()->type();
            $product = Product::create(
                $this->workspace->current(),
                $this->references->generate($type, $declination->productName()),
                $declination->productName(),
                $declination->sellingPrice(),
                $declination->buyingPrice(),
                $declination->variants(),
                $type,
            );
            $this->products->add($product);
            $declination->linkProduct($product->id());
        }

        return \count($declinations);
    }
}
