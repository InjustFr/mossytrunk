<?php

declare(strict_types=1);

namespace App\Domain\Design;

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
                throw InvalidDesign::emptyAdaptation();
            }
            if (\in_array($value, $clean, true)) {
                throw InvalidDesign::duplicateAdaptation($value);
            }
            $clean[] = $value;
        }

        return $clean;
    }
}
