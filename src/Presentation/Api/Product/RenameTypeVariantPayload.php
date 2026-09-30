<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RenameTypeVariantPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'variant.blank')]
        public string $from = '',
        #[Assert\NotBlank(message: 'variant.blank')]
        #[Assert\Length(max: 100)]
        public string $to = '',
    ) {
    }
}
