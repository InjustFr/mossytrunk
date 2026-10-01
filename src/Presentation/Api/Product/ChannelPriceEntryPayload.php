<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelPriceEntryPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'channel.invalid')]
        #[Assert\Ulid(message: 'channel.invalid')]
        public string $channelId = '',
        #[Assert\PositiveOrZero(message: 'channelPrice.negative')]
        public ?int $price = null,
    ) {
    }
}
