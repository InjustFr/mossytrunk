<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Domain\Discount\DiscountActionKind;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountActionPayload
{
    public function __construct(
        #[Assert\Choice(callback: [self::class, 'kinds'], message: 'discount.action.invalid')]
        public string $kind = 'fixedPrice',
        #[Assert\Positive(message: 'discount.action.value.positive')]
        public int $value = 0,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return array_map(static fn (DiscountActionKind $kind): string => $kind->value, DiscountActionKind::cases());
    }
}
