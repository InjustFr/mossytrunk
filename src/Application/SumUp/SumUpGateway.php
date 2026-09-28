<?php

declare(strict_types=1);

namespace App\Application\SumUp;

/**
 * Port to the SumUp account. Implemented by Infrastructure\SumUp\SumUpApiGateway (real API)
 * and Infrastructure\SumUp\FakeSumUpGateway (test env, e2e).
 */
interface SumUpGateway
{
    /**
     * Every successful payment of the account, oldest first.
     *
     * @return iterable<SumUpTransaction>
     *
     * @throws SumUpUnavailable
     */
    public function successfulPayments(SumUpCredentials $credentials): iterable;
}
