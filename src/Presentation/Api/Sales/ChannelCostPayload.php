<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Domain\Sales\ChannelCostKind;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelCostPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'channelCost.label.required')]
        #[Assert\Length(max: 100)]
        public string $label = '',
        #[Assert\Choice(callback: [self::class, 'kinds'], message: 'channelCost.kind.invalid')]
        public string $kind = 'fixed',
        #[Assert\PositiveOrZero(message: 'channelCost.amount.negative')]
        public int $amount = 0,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return array_column(ChannelCostKind::cases(), 'value');
    }

    public function kind(): ChannelCostKind
    {
        return ChannelCostKind::from($this->kind);
    }
}
