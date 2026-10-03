<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Fixtures\Story\ConventionSeasonStory;
use App\Fixtures\Story\OtherWorkspaceStory;
use App\Fixtures\Story\ProductionVolumeStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Loaded by `make fixtures` (doctrine:fixtures:load): realistic data for local testing.
 */
final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        ConventionSeasonStory::load();
        OtherWorkspaceStory::load();
        ProductionVolumeStory::load();
    }
}
