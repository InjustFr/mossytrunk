<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReceptionPayload
{
    /**
     * @param list<ReceivedLinePayload> $lines
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $lines = [],
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function receivedQuantities(): array
    {
        $quantities = [];
        foreach ($this->lines as $line) {
            $quantities[$line->lineId] = $line->received;
        }

        return $quantities;
    }
}
