<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Identity\Exception\InvalidThemeColor;

final readonly class Theme
{
    private const float DARK_LUMINANCE = 0.24;

    private function __construct(
        public string $background,
        public string $accent,
    ) {
    }

    public static function of(string $background, string $accent): self
    {
        return new self(self::hexColor($background), self::hexColor($accent));
    }

    public function isDark(): bool
    {
        return self::luminance($this->background) < self::DARK_LUMINANCE;
    }

    private static function hexColor(string $color): string
    {
        $color = mb_strtolower(trim($color));
        if (1 !== preg_match('/^#[0-9a-f]{6}$/', $color)) {
            throw new InvalidThemeColor($color);
        }

        return $color;
    }

    private static function luminance(string $color): float
    {
        [$red, $green, $blue] = array_map(
            static fn (string $channel): float => self::linear(hexdec($channel) / 255),
            str_split(substr($color, 1), 2),
        );

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    private static function linear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
