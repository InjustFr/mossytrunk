<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Application\Integration\Authorization;
use App\Application\Integration\AuthorizingConnector;
use App\Application\Integration\Credentials;
use App\Application\Integration\Exception\ServiceNotConnected;
use App\Application\Integration\LinePrices;
use App\Application\Integration\ServiceDescription;
use App\Application\Integration\ServiceField;
use App\Application\Integration\Tokens;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;

final readonly class EtsyConnector implements AuthorizingConnector
{
    public const string KEY = 'etsy';

    public function __construct(private EtsyGateway $gateway)
    {
    }

    public function describe(): ServiceDescription
    {
        return new ServiceDescription(
            self::KEY,
            'Etsy',
            'services.etsy.summary',
            [
                new ServiceField('keystring', 'services.etsy.fields.keystring', pattern: '/^[A-Za-z0-9]{1,64}$/', patternMessage: 'services.fields.alphanumeric', maxLength: 64),
                new ServiceField('shared_secret', 'services.etsy.fields.sharedSecret', secret: true, maxLength: 200),
            ],
            SalesContext::Online,
            UnknownItems::LinkByHand,
            LinePrices::Listed,
            'services.etsy.instructions',
        );
    }

    public function sales(Credentials $credentials): iterable
    {
        $shopId = $credentials->accountId ?? throw new ServiceNotConnected('Etsy');

        return $this->gateway->paidReceipts(self::app($credentials), $this->accessToken($credentials), $shopId);
    }

    public function authorizationUrl(Credentials $credentials, string $redirectUri, string $state, string $codeChallenge): string
    {
        return $this->gateway->authorizationUrl(self::app($credentials), $redirectUri, $state, $codeChallenge);
    }

    public function authorize(Credentials $credentials, string $code, string $codeVerifier, string $redirectUri): Authorization
    {
        $app = self::app($credentials);
        $tokens = $this->gateway->exchangeCode($app, $code, $codeVerifier, $redirectUri);
        $shop = $this->gateway->shop($app, $tokens->accessToken);

        return new Authorization($tokens, $shop->id, $shop->name);
    }

    public function refresh(Credentials $credentials): Tokens
    {
        return $this->gateway->refresh(self::app($credentials), $credentials->refreshToken ?? throw new ServiceNotConnected('Etsy'));
    }

    private function accessToken(Credentials $credentials): string
    {
        return $credentials->accessToken ?? throw new ServiceNotConnected('Etsy');
    }

    private static function app(Credentials $credentials): EtsyApp
    {
        return new EtsyApp($credentials->get('keystring'), $credentials->get('shared_secret'));
    }
}
