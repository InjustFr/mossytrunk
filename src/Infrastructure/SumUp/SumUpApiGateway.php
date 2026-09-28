<?php

declare(strict_types=1);

namespace App\Infrastructure\SumUp;

use App\Application\SumUp\SumUpGateway;
use App\Application\SumUp\SumUpUnavailable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reads successful payments through the SumUp REST API:
 *  - GET /v2.1/merchants/{code}/transactions/history (paginated with `links[rel=next]`)
 *  - GET /v2.1/merchants/{code}/transactions?id={id} for each payment, to get its `products`.
 */
final readonly class SumUpApiGateway implements SumUpGateway
{
    private const int PAGE_SIZE = 100;

    public function __construct(
        #[Target('sumup.client')]
        private HttpClientInterface $client,
        private SumUpPayloadMapper $mapper,
        #[Autowire(env: 'SUMUP_API_KEY')]
        private string $apiKey,
        #[Autowire(env: 'SUMUP_MERCHANT_CODE')]
        private string $merchantCode,
    ) {
    }

    public function successfulPayments(): iterable
    {
        if ('' === $this->apiKey || '' === $this->merchantCode) {
            throw SumUpUnavailable::notConfigured();
        }

        try {
            $query = http_build_query(['order' => 'ascending', 'limit' => self::PAGE_SIZE, 'statuses[]' => 'SUCCESSFUL', 'types[]' => 'PAYMENT']);

            while (null !== $query) {
                $page = $this->get(\sprintf('/v2.1/merchants/%s/transactions/history?%s', rawurlencode($this->merchantCode), $query));

                foreach ($page['items'] ?? [] as $item) {
                    $details = $this->get(\sprintf('/v2.1/merchants/%s/transactions?%s', rawurlencode($this->merchantCode), http_build_query(['id' => $item['id']])));

                    yield $this->mapper->transaction($details + $item);
                }

                $query = self::nextPageQuery($page);
            }
        } catch (ExceptionInterface $exception) {
            throw SumUpUnavailable::failed($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        return $this->client->request('GET', $url, ['auth_bearer' => $this->apiKey])->toArray();
    }

    /**
     * @param array<string, mixed> $page
     */
    private static function nextPageQuery(array $page): ?string
    {
        foreach ($page['links'] ?? [] as $link) {
            if ('next' === ($link['rel'] ?? null) && '' !== ($link['href'] ?? '')) {
                return ltrim((string) $link['href'], '?');
            }
        }

        return null;
    }
}
