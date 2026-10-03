<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notebook;

use App\Domain\Notebook\Gap;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\NotebookReconciliation;
use App\Domain\Notebook\RecordedLine;
use App\Domain\Notebook\RecordedOrder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class NotebookReconciliationTest extends TestCase
{
    private Ulid $prints;
    private Ulid $stickers;
    private Ulid $dragon;
    private Ulid $fox;
    private Ulid $moss;
    private int $minute = 0;

    protected function setUp(): void
    {
        $this->prints = new Ulid();
        $this->stickers = new Ulid();
        $this->dragon = new Ulid();
        $this->fox = new Ulid();
        $this->moss = new Ulid();
    }

    public function testAnEntryMatchesTheOrderSellingTheSameProductsInTheSameQuantities(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 2), $this->noted($this->moss, 1))],
            [$this->order('CMD-1', $this->sold($this->moss, 1), $this->sold($this->dragon, 2))],
        );

        self::assertCount(1, $reconciliation->matching());
        self::assertSame([], $reconciliation->differing());
        self::assertSame([], $reconciliation->notRecorded);
        self::assertSame([], $reconciliation->notNoted);
    }

    public function testAnEntryNamingOnlyTheTypeMatchesAnyProductOfThatType(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry(new NotebookLine('2 stickers', 2, 'Sticker', typeId: $this->stickers))],
            [$this->order('CMD-1', $this->sold($this->moss, 1, type: $this->stickers), $this->sold($this->fox, 1, type: $this->stickers))],
        );

        self::assertCount(1, $reconciliation->matching());
    }

    public function testAProductNotedWithoutVariantMatchesAnyOfItsVariants(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 1))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1, 'A4'))],
        );

        self::assertCount(1, $reconciliation->matching());
    }

    public function testAPairedEntryShowsWhatWasOnlyNotedAndWhatWasOnlySold(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 2, 'A4'), $this->noted($this->moss, 1))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1, 'A4'), $this->sold($this->dragon, 1, 'A3'), $this->sold($this->moss, 1))],
        );

        $pairing = $reconciliation->differing()[0];
        self::assertEquals([new Gap('Dragon — A4', 1)], $pairing->comparison->onlyNoted);
        self::assertEquals([new Gap('Dragon — A3', 1)], $pairing->comparison->onlySold);
        self::assertSame([], $reconciliation->notRecorded);
        self::assertSame([], $reconciliation->notNoted);
    }

    public function testTheSameProductInAnotherVariantPairsTheSaleWithItsDifference(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 1, 'A4'))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1, 'A3'))],
        );

        $pairing = $reconciliation->differing()[0];
        self::assertEquals([new Gap('Dragon — A4', 1)], $pairing->comparison->onlyNoted);
        self::assertEquals([new Gap('Dragon — A3', 1)], $pairing->comparison->onlySold);
    }

    public function testAnotherProductOfTheSameTypeIsNotTheSameSale(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->fox, 1))],
            [$this->order('CMD-1', $this->sold($this->moss, 1))],
        );

        self::assertSame([], $reconciliation->pairings);
    }

    public function testSalesAreComparedOneByOneEvenWhenBothSidesCountTheSameNumberOfOrders(): void
    {
        $forgottenOnTheTerminal = $this->entry($this->noted($this->fox, 3));
        $forgottenInTheNotebook = $this->order('CMD-3', $this->sold($this->moss, 5));

        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 1)), $forgottenOnTheTerminal, $this->entry($this->noted($this->moss, 1))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1)), $this->order('CMD-2', $this->sold($this->moss, 1)), $forgottenInTheNotebook],
        );

        self::assertCount(2, $reconciliation->matching());
        self::assertSame([1 => $forgottenOnTheTerminal], $reconciliation->notRecorded);
        self::assertSame([$forgottenInTheNotebook], $reconciliation->notNoted);
    }

    public function testIdenticalSalesArePairedFollowingTheirOrderOfSale(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->moss, 1)), $this->entry($this->noted($this->dragon, 1)), $this->entry($this->noted($this->moss, 1))],
            [$this->order('CMD-1', $this->sold($this->moss, 1)), $this->order('CMD-2', $this->sold($this->dragon, 1)), $this->order('CMD-3', $this->sold($this->moss, 1))],
        );

        self::assertSame(
            [[0, 'CMD-1'], [1, 'CMD-2'], [2, 'CMD-3']],
            array_map(static fn ($pairing): array => [$pairing->entryIndex, $pairing->order->reference], $reconciliation->pairings),
        );
    }

    public function testAnExactMatchWinsOverACloserPartialOne(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 2))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1)), $this->order('CMD-2', $this->sold($this->dragon, 2))],
        );

        self::assertSame('CMD-2', $reconciliation->matching()[0]->order->reference);
        self::assertSame('CMD-1', $reconciliation->notNoted[0]->reference);
    }

    public function testSalesSharingLessThanHalfOfTheirItemsAreNotPaired(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry($this->noted($this->dragon, 1), $this->noted($this->fox, 3))],
            [$this->order('CMD-1', $this->sold($this->dragon, 1), $this->sold($this->moss, 3))],
        );

        self::assertSame([], $reconciliation->pairings);
        self::assertCount(1, $reconciliation->notRecorded);
        self::assertCount(1, $reconciliation->notNoted);
    }

    public function testAnUnidentifiedItemNeverMatches(): void
    {
        $reconciliation = NotebookReconciliation::between(
            [$this->entry(new NotebookLine('truc vert', 1, 'truc vert'))],
            [$this->order('CMD-1', $this->sold($this->moss, 1))],
        );

        self::assertSame([], $reconciliation->pairings);
    }

    private function entry(NotebookLine ...$lines): NotebookEntry
    {
        return new NotebookEntry(1, array_values($lines));
    }

    private function noted(Ulid $product, int $quantity, ?string $variant = null): NotebookLine
    {
        return new NotebookLine('noted', $quantity, $this->label($product, $variant), $product, $variant, $this->prints);
    }

    private function order(string $reference, RecordedLine ...$lines): RecordedOrder
    {
        return new RecordedOrder(new Ulid(), $reference, new \DateTimeImmutable('2026-07-10 10:00')->modify(\sprintf('+%d minutes', ++$this->minute)), array_values($lines));
    }

    private function sold(Ulid $product, int $quantity, ?string $variant = null, ?Ulid $type = null): RecordedLine
    {
        return new RecordedLine($this->label($product, $variant), $quantity, $product, $variant, $type ?? $this->prints);
    }

    private function label(Ulid $product, ?string $variant): string
    {
        $name = match (true) {
            $product->equals($this->dragon) => 'Dragon',
            $product->equals($this->fox) => 'Renard',
            default => 'Mousse',
        };

        return null === $variant ? $name : \sprintf('%s — %s', $name, $variant);
    }
}
