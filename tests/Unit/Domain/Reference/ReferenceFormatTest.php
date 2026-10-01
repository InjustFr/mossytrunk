<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reference;

use App\Domain\Reference\Exception\UnknownReferenceToken;
use App\Domain\Reference\ReferenceFormat;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Reference\ReferenceSubject;
use App\Tests\Support\DomainExceptions;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ReferenceFormatTest extends TestCase
{
    public function testEachKindStartsWithItsHistoricalFormat(): void
    {
        $subject = ReferenceSubject::named(new \DateTimeImmutable('2026-07-10 15:00'), 'PRI', 'FOR');
        $free = static fn (string $reference): bool => false;

        self::assertMatchesRegularExpression('/^CMD-20260710-[0-9A-Z]{6}$/', ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Order)->issue($subject, $free));
        self::assertMatchesRegularExpression('/^CMF-20260710-[0-9A-Z]{6}$/', ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::SupplierOrder)->issue($subject, $free));
        self::assertSame('PRI-FOR', ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Product)->issue($subject, $free));
    }

    public function testNumbersIncreaseAndSkipTakenReferences(): void
    {
        $format = ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Order);
        $format->change('C{number:3}');
        $subject = ReferenceSubject::at(new \DateTimeImmutable('2026-07-10'));
        $taken = static fn (string $reference): bool => 'C002' === $reference;

        self::assertSame(['C001', 'C003', 'C004'], [$format->issue($subject, $taken), $format->issue($subject, $taken), $format->issue($subject, $taken)]);
        self::assertSame(5, $format->nextNumber());

        $format->restartNumbering();
        self::assertSame('C001', $format->issue($subject, $taken));
    }

    public function testATemplateThatNeverVariesIsSuffixedWhenTaken(): void
    {
        $format = ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Product);
        $subject = ReferenceSubject::named(new \DateTimeImmutable('2026-07-10'), 'PRI', 'FOR');
        $taken = static fn (string $reference): bool => \in_array($reference, ['PRI-FOR', 'PRI-FOR-2'], true);

        self::assertSame('PRI-FOR-3', $format->issue($subject, $taken));
        self::assertSame(1, $format->nextNumber(), 'a template without number leaves the numbering alone');
    }

    public function testRandomCharactersAreDrawnAgainWhenTaken(): void
    {
        $format = ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Order);
        $format->change('R{random:2}');
        $seen = [];
        $subject = ReferenceSubject::at(new \DateTimeImmutable('2026-07-10'));

        $reference = $format->issue($subject, static function (string $candidate) use (&$seen): bool {
            $seen[] = $candidate;

            return \count($seen) < 3;
        });

        self::assertCount(3, $seen);
        self::assertSame($seen[2], $reference);
        self::assertMatchesRegularExpression('/^R[0-9A-Z]{2}$/', $reference);
    }

    public function testAnInvalidTemplateKeepsTheCurrentOne(): void
    {
        $format = ReferenceFormat::standard(TestWorkspace::get(), ReferenceKind::Order);

        DomainExceptions::assertThrown(new UnknownReferenceToken('name'), static fn () => $format->change('{name}'));
        self::assertSame('CMD-{date}-{random}', $format->template()->value);
    }
}
