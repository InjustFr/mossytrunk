<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelSuppliesPayload
{
    /**
     * @param list<string> $supplyIds
     */
    public function __construct(
        #[Assert\All([new Assert\Ulid()])]
        public array $supplyIds = [],
    ) {
    }
}
