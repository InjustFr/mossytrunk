<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait SignsInClient
{
    use ActsAsUser;

    protected static function signedInClient(string $workspaceName = 'Atelier'): KernelBrowser
    {
        $client = self::createClient();
        $client->loginUser(SecurityUser::fromUser(self::createMember($workspaceName)));

        return $client;
    }
}
