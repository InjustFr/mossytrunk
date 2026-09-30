<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductTypePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'productType.name.required')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'productType.color.required')]
        public ?string $color = null,
        #[Assert\Regex(pattern: '/^\s*[A-Za-z0-9]{1,8}\s*$|^\s*$/', message: 'productType.code.invalid')]
        public ?string $code = null,
    ) {
    }
}
