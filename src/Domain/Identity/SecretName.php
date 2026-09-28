<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum SecretName: string
{
    case SumUpApiKey = 'sumup_api_key';
}
