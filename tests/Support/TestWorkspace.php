<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Identity\Workspace;

final class TestWorkspace
{
    private static ?Workspace $workspace = null;

    public static function get(): Workspace
    {
        return self::$workspace ??= Workspace::create('Atelier');
    }
}
