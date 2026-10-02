<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostagePayload
{
    public function __construct(
        #[Assert\PositiveOrZero(message: 'postage.negative')]
        public int $postage = 0,
    ) {
    }
}
