<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class EmptyWorkspaceName extends InvalidAccount
{
    public function __construct()
    {
        parent::__construct('identity.empty_workspace_name');
    }
}
