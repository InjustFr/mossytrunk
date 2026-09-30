<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProductTypePayload
{
    /**
     * @param list<string>|null $variants
     * @param list<string>|null $archivedVariants
     */
    public function __construct(
        #[Assert\NotBlank(message: 'productType.name.required')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\NotBlank(message: 'productType.color.required')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'productType.color.required')]
        public string $color = '',
        #[Assert\Regex(pattern: '/^\s*[A-Za-z0-9]{1,8}\s*$/', message: 'productType.code.invalid')]
        public ?string $code = null,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank'), new Assert\Length(max: 100)])]
        public ?array $variants = null,
        public ?bool $prefixesNames = null,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank'), new Assert\Length(max: 100)])]
        public ?array $archivedVariants = null,
    ) {
    }
}
