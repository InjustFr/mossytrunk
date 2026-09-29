<?php

declare(strict_types=1);

namespace App\Fixtures\Story;

use App\Domain\Shared\Money;
use App\Fixtures\Factory\ProductFactory;
use App\Fixtures\Factory\ProductTypeFactory;
use App\Fixtures\Factory\UserFactory;
use App\Fixtures\Factory\WorkspaceFactory;
use Zenstruck\Foundry\Story;

final class OtherWorkspaceStory extends Story
{
    public function build(): void
    {
        $workspace = WorkspaceFactory::createOne(['name' => 'Autre atelier']);
        UserFactory::new()->withPassword('mossytrunk')->create(['email' => 'autre@mossytrunk.local', 'workspace' => $workspace]);

        $ceramic = ProductTypeFactory::createOne(['workspace' => $workspace, 'name' => 'Céramique', 'code' => 'CER']);
        foreach (['Bol' => 3_500, 'Tasse' => 2_800, 'Vase' => 6_000] as $name => $price) {
            ProductFactory::createOne([
                'workspace' => $workspace,
                'type' => $ceramic,
                'reference' => 'CER-'.strtoupper($name),
                'name' => $name,
                'sellingPrice' => Money::cents($price),
            ])->bought(Money::cents(intdiv($price, 3)));
        }
    }
}
