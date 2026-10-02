<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Domain\Order\PaymentMethod;
use App\Domain\Sales\ChannelCostKind;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PaymentFeePayload
{
    public function __construct(
        #[Assert\Choice(choices: ['card', 'cash', 'mixed'], message: 'paymentFee.paymentMethod.invalid')]
        public string $paymentMethod = 'card',
        #[Assert\NotBlank(message: 'channelCost.label.required')]
        #[Assert\Length(max: 100)]
        public string $label = '',
        #[Assert\Choice(choices: ['fixed', 'percent'], message: 'channelCost.kind.invalid')]
        public string $kind = 'percent',
        #[Assert\PositiveOrZero(message: 'channelCost.amount.negative')]
        public int $amount = 0,
    ) {
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->paymentMethod);
    }

    public function kind(): ChannelCostKind
    {
        return ChannelCostKind::from($this->kind);
    }
}
