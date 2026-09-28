<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum PasswordTokenPurpose: string
{
    case Invitation = 'invitation';

    case Reset = 'reset';

    public function lifetime(): \DateInterval
    {
        return match ($this) {
            self::Invitation => new \DateInterval('P7D'),
            self::Reset => new \DateInterval('PT1H'),
        };
    }
}
