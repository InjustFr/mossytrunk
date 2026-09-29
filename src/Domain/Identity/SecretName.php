<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum SecretName: string
{
    case SumUpApiKey = 'sumup_api_key';
    case EtsySharedSecret = 'etsy_shared_secret';
    case EtsyAccessToken = 'etsy_access_token';
    case EtsyRefreshToken = 'etsy_refresh_token';
}
