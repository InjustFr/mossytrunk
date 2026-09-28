<?php

declare(strict_types=1);

namespace App\Fixtures\Story;

use App\Domain\Discount\BasketLine;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\DiscountRule;
use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use App\Fixtures\Factory\DiscountRuleFactory;
use App\Fixtures\Factory\EventFactory;
use App\Fixtures\Factory\ProductFactory;
use App\Fixtures\Factory\ProductTypeFactory;
use App\Domain\Product\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\faker;

/**
 * A believable season for a small illustration shop: catalogue, bundle discounts,
 * past conventions with expenses and orders, and an upcoming one without orders yet.
 */
final class ConventionSeasonStory extends Story
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DiscountCalculator $discountCalculator,
    ) {
    }

    public function build(): void
    {
        faker()->seed(2026);

        $type = static fn (string $name, string $code): ProductType => ProductTypeFactory::createOne(['name' => $name, 'code' => $code]);
        $sticker = $type('Sticker', 'STI');
        $print = $type('Print', 'PRI');

        $stickers = [
            $this->product('STI-MOUSSE', 'Mousse', 400, 60, type: $sticker),
            $this->product('STI-FOUGERE', 'Fougère', 400, 60, type: $sticker),
            $this->product('STI-CHAMPIGNON', 'Champignon', 400, 60, type: $sticker),
            $this->product('STI-HOLO', 'Holographique', 600, 120, type: $sticker),
        ];
        $prints = [
            $this->product('PRI-FORET', 'Forêt', 1_500, 350, ['A5', 'A4', 'A3'], $print),
            $this->product('PRI-RIVIERE', 'Rivière', 1_500, 350, ['A5', 'A4', 'A3'], $print),
        ];
        $others = [
            $this->product('TSH-LICHEN', 'Lichen', 2_500, 1_100, ['S', 'M', 'L', 'XL'], $type('T-shirt', 'TSH')),
            $this->product('TOT-BAG', 'Mousse', 1_500, 600, ['Naturel', 'Noir'], $type('Tote bag', 'TOT')),
            $this->product('PIN-ESCARGOT', 'Escargot', 800, 250, type: $type('Pin\'s', 'PIN')),
            $this->product('ZIN-SOUS-BOIS', 'Sous-bois', 1_000, 0, type: $type('Zine', 'ZIN')), // buying price unknown on purpose
            $this->product('ORI-CLAIRIERE', '« Clairière »', 12_000, 0, type: $type('Aquarelle originale', 'AQU')), // unique product
        ];
        $catalogue = [...$stickers, ...$prints, ...$others];

        $rules = [
            DiscountRuleFactory::createOne(['name' => '3 stickers pour 10 €', 'eligibleProducts' => $stickers, 'bundleSize' => 3, 'bundlePrice' => Money::cents(1_000)]),
            DiscountRuleFactory::createOne(['name' => '2 prints pour 25 €', 'eligibleProducts' => $prints, 'bundleSize' => 2, 'bundlePrice' => Money::cents(2_500)]),
        ];

        $events = [
            EventFactory::new()->withExpenses(['Stand' => 18_000, 'Train' => 9_400, 'Hôtel (2 nuits)' => 16_000, 'Repas' => 4_500])
                ->create(['name' => 'Japan Expo', 'location' => 'Paris Nord Villepinte', 'period' => EventFactory::during('-80 days', '-77 days')]),
            EventFactory::new()->withExpenses(['Stand' => 6_000, 'Essence' => 3_200])
                ->create(['name' => 'Festival de la BD', 'location' => 'Angoulême', 'period' => EventFactory::during('-45 days', '-44 days')]),
            EventFactory::new()->withExpenses(['Emplacement' => 2_500])
                ->create(['name' => 'Marché des créateurs', 'location' => 'Lyon', 'period' => EventFactory::during('-12 days', '-12 days')]),
            EventFactory::new()->withExpenses(['Stand (acompte)' => 5_000])
                ->create(['name' => 'Salon fantastique', 'location' => 'Lille', 'period' => EventFactory::during('+20 days', '+21 days')]),
        ];

        foreach ([$events[0], $events[1], $events[2]] as $index => $event) {
            $this->sellDuring($event, $catalogue, $rules, [45, 25, 15][$index]);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<string> $variants
     */
    private function product(string $reference, string $name, int $selling, int $buying, array $variants = [], ?ProductType $type = null): Product
    {
        return ProductFactory::createOne([
            'type' => $type,
            'reference' => $reference,
            'name' => $name,
            'sellingPrice' => Money::cents($selling),
            'buyingPrice' => Money::cents($buying),
            'variants' => $variants,
        ]);
    }

    /**
     * @param list<Product>      $catalogue
     * @param list<DiscountRule> $rules
     */
    private function sellDuring(Event $event, array $catalogue, array $rules, int $orderCount): void
    {
        $days = (int) $event->period()->start()->diff($event->period()->end())->days;

        for ($i = 0; $i < $orderCount; ++$i) {
            $placedAt = $event->period()->start()
                ->modify(\sprintf('+%d days', faker()->numberBetween(0, $days)))
                ->setTime(faker()->numberBetween(10, 18), faker()->numberBetween(0, 59));

            $items = [];
            foreach (faker()->randomElements($catalogue, faker()->numberBetween(1, 3)) as $product) {
                $variant = $product->hasVariants() ? faker()->randomElement($product->variants()) : null;
                $quantity = str_starts_with($product->reference(), 'STI') ? faker()->numberBetween(1, 5) : 1;
                $items[] = new OrderedItem($product->sellable($variant), $quantity);
            }

            $basket = array_map(static fn (OrderedItem $ordered): BasketLine => new BasketLine($ordered->item->productId, $ordered->item->sellingPrice, $ordered->quantity), $items);
            $this->entityManager->persist(Order::place($event, $placedAt, $items, $this->discountCalculator->calculate($basket, $rules)));
        }
    }
}
