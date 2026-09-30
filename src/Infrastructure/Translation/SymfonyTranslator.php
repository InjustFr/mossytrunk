<?php

declare(strict_types=1);

namespace App\Infrastructure\Translation;

use App\Application\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SymfonyTranslator implements Translator
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, 'messages');
    }
}
