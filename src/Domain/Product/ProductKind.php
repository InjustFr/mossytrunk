<?php

declare(strict_types=1);

namespace App\Domain\Product;

enum ProductKind: string
{
    case Article = 'article';
    case Supply = 'supply';
}
