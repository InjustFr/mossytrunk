<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use App\Domain\Identity\Exception\InvalidThemeColor;
use App\Domain\Identity\Theme;
use App\Domain\Identity\User;
use App\Domain\Identity\Workspace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    public function testColorsAreNormalized(): void
    {
        $theme = Theme::of(' #F5F5F3 ', '#5B7F3A');

        self::assertSame('#f5f5f3', $theme->background);
        self::assertSame('#5b7f3a', $theme->accent);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidColors(): iterable
    {
        yield 'short hex' => ['#fff'];
        yield 'no hash' => ['f5f5f3'];
        yield 'named colour' => ['white'];
        yield 'not hexadecimal' => ['#f5f5g3'];
    }

    #[DataProvider('invalidColors')]
    public function testRejectsAColorThatIsNotALongHexCode(string $color): void
    {
        $this->expectException(InvalidThemeColor::class);

        Theme::of($color, '#5b7f3a');
    }

    public function testADarkBackgroundMakesADarkTheme(): void
    {
        self::assertTrue(Theme::of('#1d201b', '#93bb6c')->isDark());
        self::assertFalse(Theme::of('#f5f5f3', '#5b7f3a')->isDark());
        self::assertFalse(Theme::of('#9a9a95', '#5b7f3a')->isDark());
    }

    public function testAUserWearsTheThemeTheyChose(): void
    {
        $user = User::invite('louis@example.com', Workspace::create('Atelier'));
        self::assertNull($user->theme());

        $user->wear(Theme::of('#eef2f5', '#2f6a8f'));

        self::assertEquals(Theme::of('#eef2f5', '#2f6a8f'), $user->theme());
    }
}
