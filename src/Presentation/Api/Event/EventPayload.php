<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class EventPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $location = '',
        #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
        #[Assert\Date(message: 'Date invalide.')]
        public string $startDate = '',
        #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
        #[Assert\Date(message: 'Date invalide.')]
        public string $endDate = '',
    ) {
    }

    public function start(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->startDate);
    }

    public function end(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->endDate);
    }
}
