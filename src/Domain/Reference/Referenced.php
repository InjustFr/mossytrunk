<?php

declare(strict_types=1);

namespace App\Domain\Reference;

interface Referenced
{
    public function reference(): string;

    public function referenceSubject(): ReferenceSubject;

    public function changeReference(string $reference): void;
}
