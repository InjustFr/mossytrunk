<?php

declare(strict_types=1);

namespace App\Application;

interface Translator
{
    /**
     * @param array<string, string|int> $parameters
     */
    public function trans(string $key, array $parameters = []): string;
}
