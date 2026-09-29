<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\SumUp;

use App\Application\Integration\Credentials;
use App\Application\Integration\LinePrices;
use App\Application\Integration\SalesConnector;
use App\Application\Integration\ServiceDescription;
use App\Application\Integration\ServiceField;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;

final readonly class SumUpConnector implements SalesConnector
{
    public const string KEY = 'sumup';

    public function __construct(private SumUpGateway $gateway)
    {
    }

    public function describe(): ServiceDescription
    {
        return new ServiceDescription(
            self::KEY,
            'SumUp',
            'Ventes et produits du terminal de paiement',
            [
                new ServiceField('merchant_code', 'Code marchand', pattern: '/^[A-Za-z0-9]{1,32}$/', patternMessage: 'Lettres et chiffres uniquement.', maxLength: 32, uppercase: true),
                new ServiceField('api_key', 'Clé API', secret: true, hint: 'Tableau de bord SumUp › Clés API, commence par sup_sk_.', maxLength: 500),
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
}
