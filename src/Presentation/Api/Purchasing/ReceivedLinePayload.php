<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReceivedLinePayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Ulid]
        public string $lineId = '',
        #[Assert\PositiveOrZero(message: 'La quantité reçue ne peut pas être négative.')]
        public int $received = 0,
    ) {
    }
}
