<?php

declare(strict_types=1);

namespace App\Infrastructure\SumUp;

use App\Application\SumUp\SumUpCredentials;
use App\Application\SumUp\SumUpGateway;
use App\Application\SumUp\SumUpUnavailable;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Reads successful payments through the SumUp REST API:
 *  - GET /v2.1/merchants/{code}/transactions/history (paginated with `links[rel=next]`)
 *  - GET /v2.1/merchants/{code}/transactions?id={id} for each payment of a page, all sent at once, to get its `products`.
 */
final readonly class SumUpApiGateway implements SumUpGateway
{
    private const int PAGE_SIZE = 100;

    public function __construct(
        #[Target('sumup.client')]
        private HttpClientInterface $client,
        private SumUpPayloadMapper $mapper,
    ) {
    }

    public function successfulPayments(SumUpCredentials $credentials): iterable
    {
        try {
            $query = http_build_query(['order' => 'ascending', 'limit' => self::PAGE_SIZE, 'statuses[]' => 'SUCCESSFUL', 'types[]' => 'PAYMENT']);

            while (null !== $query) {
                $page = $this->get($credentials, \sprintf('/v2.1/merchants/%s/transactions/history?%s', rawurlencode($credentials->merchantCode), $query));

                $details = [];
                foreach (SumUpJson::objects($page['items'] ?? []) as $item) {
                    $details[] = [$item, $this->request($credentials, \sprintf('/v2.1/merchants/%s/transactions?%s', rawurlencode($credentials->merchantCode), http_build_query(['id' => SumUpJson::string($item['id'] ?? '')])))];
                }

                foreach ($details as [$item, $response]) {
                    yield $this->mapper->transaction(SumUpJson::object($response->toArray()) + $item);
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
    private function get(SumUpCredentials $credentials, string $url): array
    {
        return SumUpJson::object($this->request($credentials, $url)->toArray());
    }

    private function request(SumUpCredentials $credentials, string $url): ResponseInterface
    {
        return $this->client->request('GET', $url, ['auth_bearer' => $credentials->apiKey]);
    }

    /**
     * @param array<string, mixed> $page
     */
    private static function nextPageQuery(array $page): ?string
    {
        foreach (SumUpJson::objects($page['links'] ?? []) as $link) {
            $href = SumUpJson::string($link['href'] ?? '');
            if ('next' === ($link['rel'] ?? null) && '' !== $href) {
                return ltrim($href, '?');
            }
        }

        return null;
    }
}
