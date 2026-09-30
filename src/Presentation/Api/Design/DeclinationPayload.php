<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\AdjustDeclination\AdjustDeclination;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeclinationPayload
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        #[Assert\NotBlank(message: 'product.name.required')]
        #[Assert\Length(max: 255)]
        public string $productName = '',
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank')])]
        #[Assert\Unique(message: 'variant.duplicate')]
        public array $variants = [],
    ) {
    }

    public function toCommand(string $designId, string $declinationId): AdjustDeclination
    {
        return new AdjustDeclination($designId, $declinationId, $this->productName, $this->sellingPrice, $this->variants);
    }
}
