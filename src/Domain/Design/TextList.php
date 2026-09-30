<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Design\Exception\DuplicateAdaptation;
use App\Domain\Design\Exception\EmptyAdaptation;

final class TextList
{
    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    public static function clean(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ('' === $value) {
                throw new EmptyAdaptation();
            }
            if (\in_array($value, $clean, true)) {
                throw new DuplicateAdaptation($value);
            }
            $clean[] = $value;
        }

        return $clean;
    }
}
