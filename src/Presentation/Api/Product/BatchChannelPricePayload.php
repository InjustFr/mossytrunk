<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\BatchUpdateProducts\ChannelPriceChange;
use App\Application\Product\BatchUpdateProducts\ChannelPriceMode;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class BatchChannelPricePayload
{
    public const string CENTS = 'cents';
    public const string PERCENT = 'percent';

    public function __construct(
        #[Assert\Ulid(message: 'channel.invalid')]
        public ?string $channelId = null,
        #[Assert\Choice(callback: [self::class, 'modes'], message: 'channelPrice.mode.invalid')]
        public string $mode = 'fixed',
        #[Assert\PositiveOrZero(message: 'channelPrice.negative')]
        public ?int $price = null,
        #[Assert\Ulid(message: 'channel.invalid')]
        public ?string $sourceChannelId = null,
        public float $adjustment = 0,
        #[Assert\Choice(choices: [self::CENTS, self::PERCENT], message: 'channelPrice.unit.invalid')]
        public string $adjustmentUnit = self::CENTS,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function modes(): array
    {
        return array_column(ChannelPriceMode::cases(), 'value');
    }

    #[Assert\Callback]
    public function requireAPrice(ExecutionContextInterface $context): void
    {
        if (ChannelPriceMode::Fixed->value === $this->mode && null === $this->price) {
            $context->buildViolation('channelPrice.required')->atPath('price')->addViolation();
        }
        if (ChannelPriceMode::Derived->value === $this->mode && $this->channelId === $this->sourceChannelId) {
            $context->buildViolation('channelPrice.sameSource')->atPath('sourceChannelId')->addViolation();
        }
    }

    public function toChange(): ChannelPriceChange
    {
        $percent = self::PERCENT === $this->adjustmentUnit;

        return new ChannelPriceChange(
            $this->channelId,
            ChannelPriceMode::from($this->mode),
            $this->price,
            $this->sourceChannelId,
            $percent ? 0 : (int) round($this->adjustment),
            $percent ? (int) round($this->adjustment * 100) : 0,
        );
    }
}
