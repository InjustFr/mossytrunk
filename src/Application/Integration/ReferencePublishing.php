<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface ReferencePublishing extends SalesConnector
{
    /**
     * @param list<ItemReference> $references
     */
    public function publishReferences(Credentials $credentials, array $references): int;
}
