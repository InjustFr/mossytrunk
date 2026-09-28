<?php

declare(strict_types=1);

namespace App\Domain\Identity;

interface PasswordTokenRepository
{
    public function add(PasswordToken $token): void;

    public function findBySelector(string $selector): ?PasswordToken;

    public function removeAllFor(User $user): void;
}
