<?php

declare(strict_types=1);

namespace App\Presentation\Api\Workspace;

use App\Application\Workspace\UpdateSumUpSettings\UpdateSumUpSettings;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SumUpSettingsPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le code marchand est obligatoire.')]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9]{1,32}$/', message: 'Le code marchand ne contient que des lettres et des chiffres.')]
        public string $merchantCode = '',
        #[Assert\Length(max: 500, maxMessage: 'Clé trop longue.')]
        public ?string $apiKey = null,
    ) {
    }

    public function toCommand(): UpdateSumUpSettings
    {
        return new UpdateSumUpSettings($this->merchantCode, $this->apiKey);
    }
}
