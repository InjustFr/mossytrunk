<?php

declare(strict_types=1);

namespace App\Application\Design\ListDesigns;

use App\Application\Design\DesignView;
use App\Domain\Design\Design;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;

final readonly class ListDesignsHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
    ) {
    }

    public function __invoke(): DesignBoard
    {
        $byCollection = [];
        $standalone = [];
        foreach ($this->designs->all() as $design) {
            if (null === $design->collection()) {
                $standalone[] = DesignView::of($design);
            } else {
                $byCollection[(string) $design->collection()->id()][] = $design;
            }
        }

        return new DesignBoard(
            array_map(static function (DesignCollection $collection) use ($byCollection): array {
                $designs = $byCollection[(string) $collection->id()] ?? [];
                $open = array_filter($designs, static fn (Design $design): bool => !$design->isValidated());

                return [
                    'id' => (string) $collection->id(),
                    'name' => $collection->name(),
                    'description' => $collection->description(),
                    'current' => $collection->isCurrent(),
                    'validated' => [] !== $designs && [] === $open,
                    'readyToValidate' => [] !== $open && [] === array_filter($open, static fn (Design $design): bool => !$design->isReadyToValidate()),
                    'designs' => array_map(DesignView::of(...), $designs),
                ];
            }, $this->collections->all()),
            $standalone,
        );
    }
}
