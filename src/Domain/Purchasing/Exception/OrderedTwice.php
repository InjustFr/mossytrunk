<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class OrderedTwice extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('« %s » figure deux fois dans la commande.', $label));
    }
}
