<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class LineAlreadyIdentified extends InvalidOrder
{
    public function __construct(string $label)
    {
        parent::__construct('order.line_already_identified', ['label' => $label]);
    }
}
