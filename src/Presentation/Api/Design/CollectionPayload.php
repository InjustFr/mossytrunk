<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CollectionPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom de la collection est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        public ?string $description = null,
    ) {
    }
}
