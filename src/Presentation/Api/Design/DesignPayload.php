<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\SaveDesign\SaveDesign;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DesignPayload
{
    /**
     * @param list<string> $gabaritIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du design est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Ulid(message: 'Collection invalide.')]
        public ?string $collectionId = null,
        public ?string $notes = null,
        #[Assert\All([new Assert\Ulid(message: 'Gabarit invalide.')])]
        public array $gabaritIds = [],
    ) {
    }

    public function toCommand(?string $designId = null): SaveDesign
    {
        return new SaveDesign($designId, $this->name, $this->collectionId, $this->notes, $this->gabaritIds);
    }
}
