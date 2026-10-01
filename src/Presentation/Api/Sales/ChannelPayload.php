<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Domain\Sales\ChannelKind;
use App\Presentation\RouteRequirement;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'channel.name.required')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\Regex('/^'.RouteRequirement::SERVICE.'$/', message: 'channel.service.invalid')]
        public ?string $service = null,
        #[Assert\Choice(callback: [self::class, 'kinds'], message: 'channel.kind.invalid')]
        public string $kind = 'online',
    ) {
    }

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return array_map(static fn (ChannelKind $kind): string => $kind->value, ChannelKind::cases());
    }

    public function kind(): ChannelKind
    {
        return ChannelKind::from($this->kind);
    }
}
