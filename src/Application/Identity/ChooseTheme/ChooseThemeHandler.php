<?php

declare(strict_types=1);

namespace App\Application\Identity\ChooseTheme;

use App\Application\Identity\CurrentUser;
use App\Application\Transaction;
use App\Domain\Identity\Theme;

final readonly class ChooseThemeHandler
{
    public function __construct(
        private CurrentUser $user,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(Theme $theme): void
    {
        $this->user->get()->wear($theme);
        $this->transaction->commit();
    }
}
