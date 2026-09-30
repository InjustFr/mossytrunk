<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ProductTypesApi
{
    /**
     * @param list<string> $variants
     */
    public static function create(KernelBrowser $client, string $name = 'Divers', array $variants = [], bool $prefixesNames = false): string
    {
        $client->jsonRequest('POST', '/api/product-types', ['name' => $name, 'variants' => $variants, 'prefixesNames' => $prefixesNames]);
        Assert::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        return Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
    }
}
