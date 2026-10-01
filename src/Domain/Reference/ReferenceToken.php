<?php

declare(strict_types=1);

namespace App\Domain\Reference;

enum ReferenceToken: string
{
    case Date = 'date';
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
    case Time = 'time';
    case Hour = 'hour';
    case Minute = 'minute';
    case Number = 'number';
    case Random = 'random';
    case Type = 'type';
    case Name = 'name';

    private const string RANDOM_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const int LARGEST_NUMBER_DIGITS = 10;
    private const int TYPE_CODE_LENGTH = 8;
    private const int NAME_CODE_LENGTH = 3;

    public function defaultSize(): ?int
    {
        return match ($this) {
            self::Year => 4,
            self::Number => 1,
            self::Random => 6,
            default => null,
        };
    }

    public function minSize(): int
    {
        return match ($this) {
            self::Year, self::Random => 2,
            default => 1,
        };
    }

    public function maxSize(): int
    {
        return match ($this) {
            self::Year => 4,
            self::Number => self::LARGEST_NUMBER_DIGITS,
            self::Random => 12,
            default => 1,
        };
    }

    public function width(int $size): int
    {
        return match ($this) {
            self::Date => 8,
            self::Time => 4,
            self::Month, self::Day, self::Hour, self::Minute => 2,
            self::Year, self::Random => $size,
            self::Number => max($size, self::LARGEST_NUMBER_DIGITS),
            self::Type => self::TYPE_CODE_LENGTH,
            self::Name => self::NAME_CODE_LENGTH,
        };
    }

    public function render(ReferenceSubject $subject, int $size, int $number): string
    {
        return match ($this) {
            self::Date => $subject->moment->format('Ymd'),
            self::Year => substr($subject->moment->format('Y'), -$size),
            self::Month => $subject->moment->format('m'),
            self::Day => $subject->moment->format('d'),
            self::Time => $subject->moment->format('Hi'),
            self::Hour => $subject->moment->format('H'),
            self::Minute => $subject->moment->format('i'),
            self::Number => str_pad((string) $number, $size, '0', \STR_PAD_LEFT),
            self::Random => self::random($size),
            self::Type => $subject->typeCode,
            self::Name => $subject->nameCode,
        };
    }

    private static function random(int $size): string
    {
        $characters = '';
        for ($i = 0; $i < $size; ++$i) {
            $characters .= self::RANDOM_ALPHABET[random_int(0, \strlen(self::RANDOM_ALPHABET) - 1)];
        }

        return $characters;
    }
}
