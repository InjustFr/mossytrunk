<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reference;

use App\Domain\Reference\Exception\EmptyReferenceTemplate;
use App\Domain\Reference\Exception\ForbiddenReferenceCharacter;
use App\Domain\Reference\Exception\InvalidReferenceTokenSize;
use App\Domain\Reference\Exception\ReferenceTemplateTooLong;
use App\Domain\Reference\Exception\ReferenceTokenWithoutSize;
use App\Domain\Reference\Exception\UnknownReferenceToken;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Reference\ReferenceSubject;
use App\Domain\Reference\ReferenceTemplate;
use App\Domain\Shared\Exception\DomainException;
use App\Tests\Support\DomainExceptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferenceTemplateTest extends TestCase
{
    public function testDateAndTimeTagsReadTheMomentInParisTime(): void
    {
        $subject = ReferenceSubject::at(new \DateTimeImmutable('2026-12-31T23:30:00+00:00'));

        self::assertSame('20270101-0030_2027_27_01_01_00_30', ReferenceTemplate::of(ReferenceKind::Order, '{date}-{time}_{year}_{year:2}_{month}_{day}_{hour}_{minute}')->render($subject, 1));
    }

    public function testTheNumberIsPaddedToItsSize(): void
    {
        $subject = ReferenceSubject::at(new \DateTimeImmutable('2026-10-01'));

        self::assertSame('F-0042', ReferenceTemplate::of(ReferenceKind::SupplierOrder, 'F-{number:4}')->render($subject, 42));
        self::assertSame('F-12345', ReferenceTemplate::of(ReferenceKind::SupplierOrder, 'F-{number:4}')->render($subject, 12_345));
        self::assertSame('F-7', ReferenceTemplate::of(ReferenceKind::SupplierOrder, 'F-{number}')->render($subject, 7));
    }

    public function testRandomCharactersAreUnambiguousUppercaseLettersAndDigits(): void
    {
        $subject = ReferenceSubject::at(new \DateTimeImmutable('2026-10-01'));

        self::assertMatchesRegularExpression('/^CMD-[0-9A-HJKMNP-TV-Z]{6}$/', ReferenceTemplate::of(ReferenceKind::Order, 'CMD-{random}')->render($subject, 1));
        self::assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{10}$/', ReferenceTemplate::of(ReferenceKind::Order, '{random:10}')->render($subject, 1));
    }

    public function testProductTagsUseTheTypeCodeAndTheNameAbbreviation(): void
    {
        $subject = ReferenceSubject::named(new \DateTimeImmutable('2026-10-01'), 'PRI', 'FOR');

        self::assertSame('PRI-FOR/2026', ReferenceTemplate::of(ReferenceKind::Product, '{type}-{name}/{year}')->render($subject, 1));
    }

    public function testNumbersAndRandomCharactersMakeATemplateVary(): void
    {
        self::assertTrue(ReferenceTemplate::of(ReferenceKind::Order, 'C{number}')->numbers());
        self::assertTrue(ReferenceTemplate::of(ReferenceKind::Order, 'C{random}')->varies());
        self::assertFalse(ReferenceTemplate::of(ReferenceKind::Order, 'C{random}')->numbers());
        self::assertFalse(ReferenceTemplate::of(ReferenceKind::Product, '{type}-{name}')->varies());
    }

    public function testTheTemplateIsTrimmed(): void
    {
        self::assertSame('CMD-{number}', ReferenceTemplate::of(ReferenceKind::Order, '  CMD-{number} ')->value);
    }

    /**
     * @return iterable<string, array{ReferenceKind, string, DomainException}>
     */
    public static function invalidTemplates(): iterable
    {
        yield 'empty' => [ReferenceKind::Order, '  ', new EmptyReferenceTemplate()];
        yield 'unknown tag' => [ReferenceKind::Order, 'CMD-{week}', new UnknownReferenceToken('week')];
        yield 'product tag on an order' => [ReferenceKind::Order, '{type}-{number}', new UnknownReferenceToken('type')];
        yield 'time of a supplier order' => [ReferenceKind::SupplierOrder, 'CMF-{time}', new UnknownReferenceToken('time')];
        yield 'size too big' => [ReferenceKind::Order, '{random:20}', new InvalidReferenceTokenSize('random', 2, 12)];
        yield 'size not a number' => [ReferenceKind::Order, '{number:x}', new InvalidReferenceTokenSize('number', 1, 10)];
        yield 'size on a tag without size' => [ReferenceKind::Order, '{date:4}', new ReferenceTokenWithoutSize('date')];
        yield 'space' => [ReferenceKind::Order, 'CMD {number}', new ForbiddenReferenceCharacter(' ')];
        yield 'accent' => [ReferenceKind::Order, 'Cé{number}', new ForbiddenReferenceCharacter('é')];
        yield 'unclosed tag' => [ReferenceKind::Order, 'CMD-{number', new ForbiddenReferenceCharacter('{')];
        yield 'too long' => [ReferenceKind::Order, str_repeat('{random:12}', 5), new ReferenceTemplateTooLong(ReferenceTemplate::MAX_LENGTH)];
    }

    #[DataProvider('invalidTemplates')]
    public function testInvalidTemplatesAreRejected(ReferenceKind $kind, string $template, DomainException $expected): void
    {
        DomainExceptions::assertThrown($expected, static fn () => ReferenceTemplate::of($kind, $template));
    }
}
