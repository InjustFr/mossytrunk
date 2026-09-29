<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\SignsInClient;
use App\Tests\Support\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DiscountRuleApiTest extends WebTestCase
{
    use SignsInClient;

    public function testCreateAndListARuleWithConditionsAnActionAndAPeriod(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print']);
        $print = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'buyingPrice' => 0, 'variants' => []]);
        $sticker = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', '/api/discount-rules', [
            'name' => '2 prints et 1 sticker pour 15 €',
            'conditions' => [['kind' => 'type', 'id' => $print, 'quantity' => 2], ['kind' => 'product', 'id' => $sticker, 'quantity' => 1]],
            'action' => ['kind' => 'fixedPrice', 'value' => 1_500],
            'startsOn' => '2026-07-01',
            'endsOn' => null,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/discount-rules');
        $rule = Json::array(Json::decode((string) $client->getResponse()->getContent()), 0);
        self::assertSame([['type', 'Print', 2], ['product', 'Mousse', 1]], array_map(static fn (mixed $c): array => [Json::at($c, 'kind'), Json::at($c, 'name'), Json::at($c, 'quantity')], Json::array($rule, 'conditions')));
        self::assertSame(['kind' => 'fixedPrice', 'value' => 1_500], $rule['action']);
        self::assertSame(['2026-07-01', null], [$rule['startsOn'], $rule['endsOn']]);
    }

    public function testInvalidConditionsAndActionAreFieldErrors(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/discount-rules', [
            'name' => 'Lot',
            'conditions' => [['kind' => 'variant', 'id' => 'x', 'quantity' => 0]],
            'action' => ['kind' => 'free', 'value' => 0],
        ]);

        self::assertResponseStatusCodeSame(422);
        $paths = array_column(Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'), 'propertyPath');
        self::assertEqualsCanonicalizing(
            ['conditions[0].kind', 'conditions[0].id', 'conditions[0].quantity', 'action.kind', 'action.value'],
            $paths,
        );
    }
}
