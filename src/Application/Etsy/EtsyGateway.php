<?php

declare(strict_types=1);

namespace App\Application\Etsy;

interface EtsyGateway
{
    public function authorizationUrl(EtsyApp $app, string $redirectUri, string $state, string $codeChallenge): string;

    public function exchangeCode(EtsyApp $app, string $code, string $codeVerifier, string $redirectUri): EtsyTokens;

    public function refresh(EtsyApp $app, string $refreshToken): EtsyTokens;

    public function shop(EtsyApp $app, string $accessToken): EtsyShop;

    /**
     * @return iterable<EtsyReceipt>
     */
    public function paidReceipts(EtsyApp $app, string $accessToken, string $shopId): iterable;
}
