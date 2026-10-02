<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductKind;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request body for creating or updating a product. Prices are integer cents; a blank reference is suggested at creation
 * and kept on update.
 */
final readonly class ProductPayload
{
    /**
     * @param list<string>                   $variants
     * @param list<ChannelPriceEntryPayload> $channelPrices
     */
    public function __construct(
        #[Assert\NotBlank(message: 'name.required')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank')])]
        #[Assert\Unique(message: 'variant.duplicate')]
        public array $variants = [],
        #[Assert\NotBlank(message: 'productType.required')]
        #[Assert\Ulid(message: 'productType.invalid')]
        public ?string $typeId = null,
        #[Assert\PositiveOrZero(message: 'product.lowStock.negative')]
        public int $lowStockThreshold = Product::DEFAULT_LOW_STOCK_THRESHOLD,
        #[Assert\Length(max: Product::REFERENCE_MAX_LENGTH, maxMessage: 'product.reference.tooLong')]
        public ?string $reference = null,
        #[Assert\Valid]
        public array $channelPrices = [],
        #[Assert\Choice(callback: [self::class, 'kinds'], message: 'product.kind.invalid')]
        public string $kind = 'article',
    ) {
    }

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return array_map(static fn (ProductKind $kind): string => $kind->value, ProductKind::cases());
    }

    public function kind(): ProductKind
    {
        return ProductKind::from($this->kind);
    }

    /**
     * @return array<string, ?int>
     */
    public function pricesByChannel(): array
    {
        $prices = [];
        foreach ($this->channelPrices as $entry) {
            $prices[$entry->channelId] = $entry->price;
        }

        return $prices;
    }
}
