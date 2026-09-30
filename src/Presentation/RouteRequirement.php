<?php

declare(strict_types=1);

namespace App\Presentation;

final class RouteRequirement
{
    public const string SERVICE = '[a-z0-9]{2,32}';
    public const string DECLARATION_PERIOD = '\d{4}-(\d{2}|T[1-4])';
}
