<?php

declare(strict_types=1);

namespace App\Presentation\Api\Etsy;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class EtsySettingsPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'La clé de l\'application (keystring) est obligatoire.')]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9]{1,64}$/', message: 'La keystring ne contient que des lettres et des chiffres.')]
        public string $keystring = '',
        #[Assert\Length(max: 200, maxMessage: 'Secret trop long.')]
        public ?string $sharedSecret = null,
    ) {
    }
}
