<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AbbreviationPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'notebook.abbreviation.required')]
        #[Assert\Length(max: 50)]
        public string $short = '',
        #[Assert\NotBlank(message: 'notebook.abbreviation.required')]
        #[Assert\Length(max: 100)]
        public string $full = '',
    ) {
    }
}
