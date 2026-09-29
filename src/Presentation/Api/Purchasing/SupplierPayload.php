<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\SaveSupplier\SaveSupplier;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupplierPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du fournisseur est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Length(max: 255)]
        public ?string $contact = null,
        public ?string $notes = null,
    ) {
    }

    public function toCommand(?string $supplierId = null): SaveSupplier
    {
        return new SaveSupplier($supplierId, $this->name, $this->contact, $this->notes);
    }
}
