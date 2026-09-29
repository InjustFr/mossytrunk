<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Application\Integration\ExternalSale;
use App\Application\Integration\Tokens;
use App\Infrastructure\Http\Json;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FakeEtsyGateway implements EtsyGateway
{
    /** @var list<ExternalSale>|null */
    private ?array $receipts = null;

    public function __construct(
        private readonly EtsyPayloadMapper $mapper,
        private readonly ClockInterface $clock,
        #[Autowire('%kernel.project_dir%/tests/Fixtures/etsy/receipts.json')]
        private readonly string $fixture,
    ) {
    }

    /**
     * @param list<ExternalSale> $receipts
     */
    public function willReturn(array $receipts): void
    {
        $this->receipts = $receipts;
    }

    public function authorizationUrl(EtsyApp $app, string $redirectUri, string $state, string $codeChallenge): string
    {
        return $redirectUri.'?'.http_build_query(['code' => 'fake-code', 'state' => $state]);
    }

    public function exchangeCode(EtsyApp $app, string $code, string $codeVerifier, string $redirectUri): Tokens
    {
        return new Tokens('fake-access', 'fake-refresh', $this->clock->now()->modify('+1 hour'));
    }

    public function refresh(EtsyApp $app, string $refreshToken): Tokens
    {
        return new Tokens('fake-access-renewed', 'fake-refresh', $this->clock->now()->modify('+1 hour'));
    }

    public function shop(EtsyApp $app, string $accessToken): EtsyShop
    {
        return new EtsyShop('12345678', 'Atelier Mousse sur Etsy');
    }

    public function paidReceipts(EtsyApp $app, string $accessToken, string $shopId): iterable
    {
        if (null !== $this->receipts) {
            return $this->receipts;
        }

        $payload = json_decode((string) file_get_contents($this->fixture), true, flags: \JSON_THROW_ON_ERROR);

        return array_map($this->mapper->sale(...), Json::objects(Json::object($payload)['results'] ?? []));
    }
}
