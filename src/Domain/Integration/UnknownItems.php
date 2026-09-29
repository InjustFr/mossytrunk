<?php

declare(strict_types=1);

namespace App\Domain\Integration;

enum UnknownItems: string
{
    case CreateProduct = 'create_product';
    case LinkByHand = 'link_by_hand';
}
