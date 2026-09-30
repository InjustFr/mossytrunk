<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteProducts;

use App\Application\Product\ProductDeletion;
use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteProductsHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductDeletion $deletion,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(DeleteProducts $command): int
    {
        $ids = array_values(array_unique($command->productIds));
        foreach ($ids as $id) {
            $this->deletion->delete($this->products->get(Ulid::fromString($id)));
        }
        $this->transaction->commit();

        return \count($ids);
    }
}
