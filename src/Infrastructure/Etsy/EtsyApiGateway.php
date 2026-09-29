<?php

declare(strict_types=1);

namespace App\Infrastructure\Etsy;

use App\Application\Etsy\EtsyApp;
use App\Application\Etsy\EtsyGateway;
use App\Application\Etsy\EtsyShop;
use App\Application\Etsy\EtsyTokens;
use App\Application\Etsy\EtsyUnavailable;
use App\Infrastructure\Http\Json;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class EtsyApiGateway implements EtsyGateway
{
    private const int PAGE_SIZE = 100;
    private const string SCOPES = 'transactions_r shops_r';

    public function __construct(
        #[Target('etsy.client')]
        private HttpClientInterface $client,
        private EtsyPayloadMapper $mapper,
        private ClockInterface $clock,
    ) {
    }

    public function authorizationUrl(EtsyApp $app, string $redirectUri, string $state, string $codeChallenge): string
    {
        return 'https://www.etsy.com/oauth/connect?'.http_build_query([
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => self::SCOPES,
            'client_id' => $app->keystring,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', \PHP_QUERY_RFC3986);
    }

    public function exchangeCode(EtsyApp $app, string $code, string $codeVerifier, string $redirectUri): EtsyTokens
    {
        return $this->tokens($app, ['grant_type' => 'authorization_code', 'client_id' => $app->keystring, 'redirect_uri' => $redirectUri, 'code' => $code, 'code_verifier' => $codeVerifier]);
    }

    public function refresh(EtsyApp $app, string $refreshToken): EtsyTokens
    {
        return $this->tokens($app, ['grant_type' => 'refresh_token', 'client_id' => $app->keystring, 'refresh_token' => $refreshToken]);
    }

    public function shop(EtsyApp $app, string $accessToken): EtsyShop
    {
        $shopId = Json::string($this->get($app, $accessToken, '/v3/application/users/me')['shop_id'] ?? '');
        if ('' === $shopId) {
            throw EtsyUnavailable::failed('ce compte Etsy n\'a pas de boutique.');
        }

        return new EtsyShop($shopId, Json::string($this->get($app, $accessToken, '/v3/application/shops/'.rawurlencode($shopId))['shop_name'] ?? $shopId));
    }

    public function paidReceipts(EtsyApp $app, string $accessToken, string $shopId): iterable
    {
        for ($offset = 0;; $offset += self::PAGE_SIZE) {
            $page = $this->get($app, $accessToken, \sprintf('/v3/application/shops/%s/receipts?%s', rawurlencode($shopId), http_build_query(['was_paid' => 'true', 'limit' => self::PAGE_SIZE, 'offset' => $offset])));
            $results = Json::objects($page['results'] ?? []);
            foreach ($results as $receipt) {
                yield $this->mapper->receipt($receipt);
            }
            if (\count($results) < self::PAGE_SIZE) {
                return;
            }
        }
    }

    /**
     * @param array<string, string> $form
     */
    private function tokens(EtsyApp $app, array $form): EtsyTokens
    {
        try {
            $payload = Json::object($this->client->request('POST', '/v3/public/oauth/token', ['body' => $form, 'headers' => ['x-api-key' => self::apiKey($app)]])->toArray());
        } catch (ExceptionInterface $exception) {
            throw EtsyUnavailable::failed($exception->getMessage());
        }

        return new EtsyTokens(
            Json::string($payload['access_token'] ?? ''),
            Json::string($payload['refresh_token'] ?? ''),
            $this->clock->now()->modify(\sprintf('+%d seconds', (int) Json::number($payload['expires_in'] ?? 3600))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function get(EtsyApp $app, string $accessToken, string $url): array
    {
        try {
            return Json::object($this->client->request('GET', $url, ['auth_bearer' => $accessToken, 'headers' => ['x-api-key' => self::apiKey($app)]])->toArray());
        } catch (ExceptionInterface $exception) {
            throw EtsyUnavailable::failed($exception->getMessage());
        }
    }

    private static function apiKey(EtsyApp $app): string
    {
        return $app->keystring.':'.$app->sharedSecret;
    }
}
