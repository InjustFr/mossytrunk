<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\SumUp;

use App\Application\Integration\CatalogueExporting;
use App\Application\Integration\Credentials;
use App\Application\Integration\LinePrices;
use App\Application\Integration\ServiceDescription;
use App\Application\Integration\ServiceField;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;

final readonly class SumUpConnector implements CatalogueExporting
{
    public const string KEY = 'sumup';

    public function __construct(
        private SumUpGateway $gateway,
        private SumUpCatalogueCsv $catalogueCsv,
    ) {
    }

    public function describe(): ServiceDescription
    {
        return new ServiceDescription(
            self::KEY,
            'SumUp',
            'services.sumup.summary',
            [
                new ServiceField('merchant_code', 'services.sumup.fields.merchantCode', pattern: '/^[A-Za-z0-9]{1,32}$/', patternMessage: 'services.fields.alphanumeric', maxLength: 32, uppercase: true),
                new ServiceField('api_key', 'services.sumup.fields.apiKey', secret: true, hint: 'services.sumup.fields.apiKeyHint', maxLength: 500),
            ],
            SalesContext::AtEvent,
            UnknownItems::CreateProduct,
            LinePrices::MayBeDiscounted,
        );
    }

    public function sales(Credentials $credentials): iterable
    {
        return $this->gateway->successfulPayments(new SumUpCredentials($credentials->get('api_key'), $credentials->get('merchant_code')));
    }

    public function catalogue(array $items): string
    {
        return $this->catalogueCsv->of($items);
    }
}
