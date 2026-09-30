<?php

declare(strict_types=1);

namespace App\Application\Identity\ChooseLanguage;

use App\Application\Identity\CurrentUser;
use App\Application\Transaction;
use App\Domain\Identity\Language;

final readonly class ChooseLanguageHandler
{
    public function __construct(
        private CurrentUser $user,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(Language $language): void
    {
        $this->user->get()->speak($language);
        $this->transaction->commit();
    }
}
