<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

interface UrssafDeclarationRepository
{
    public function add(UrssafDeclaration $declaration): void;

    public function remove(UrssafDeclaration $declaration): void;

    public function find(string $period): ?UrssafDeclaration;

    /**
     * @return list<UrssafDeclaration>
     */
    public function all(): array;
}
