<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\CreateDesign\CreateDesign;
use App\Application\Design\UpdateDesign\UpdateDesign;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DesignPayload
{
    /**
     * @param list<string> $gabaritIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'design.name.required')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Ulid(message: 'collection.invalid')]
        public ?string $collectionId = null,
        public ?string $notes = null,
        #[Assert\All([new Assert\Ulid(message: 'template.invalid')])]
        public array $gabaritIds = [],
    ) {
    }

    public function toCreate(): CreateDesign
    {
        return new CreateDesign($this->name, $this->collectionId, $this->notes, $this->gabaritIds);
    }

    public function toUpdate(string $designId): UpdateDesign
    {
        return new UpdateDesign($designId, $this->name, $this->collectionId, $this->notes);
    }
}
