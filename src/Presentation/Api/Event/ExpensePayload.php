<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExpensePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $label = '',
        #[Assert\Positive(message: 'Le montant doit être supérieur à zéro.')]
        public int $amount = 0,
    ) {
    }
}
