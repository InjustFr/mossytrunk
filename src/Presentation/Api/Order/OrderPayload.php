<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RequestedLine;
use App\Domain\Shared\DateRange;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderPayload
{
    /**
     * @param list<OrderLinePayload> $lines
     */
    public function __construct(
        #[Assert\NotBlank(message: 'La date est obligatoire.')]
        #[Assert\Regex('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', message: 'Date invalide.')]
        public string $placedAt = '',
        #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un produit.')]
        #[Assert\Valid]
        public array $lines = [],
    ) {
    }

    /**
     * The UI sends a local date-time (no offset): it is Europe/Paris time.
     */
    public function placedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->placedAt, new \DateTimeZone(DateRange::TIMEZONE));
    }

    /**
     * @return list<RequestedLine>
     */
    public function requestedLines(): array
    {
        return array_map(static fn (OrderLinePayload $line): RequestedLine => $line->toRequestedLine(), $this->lines);
    }
}
