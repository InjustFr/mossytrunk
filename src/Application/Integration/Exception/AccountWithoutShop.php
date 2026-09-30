<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class AccountWithoutShop extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct('integration.account_without_shop', ['service' => $label]);
    }
}
