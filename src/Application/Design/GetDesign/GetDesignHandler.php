<?php

declare(strict_types=1);

namespace App\Application\Design\GetDesign;

use App\Application\Design\DesignView;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
    ) {
    }

    public function __invoke(string $designId): DesignView
    {
        return DesignView::of($this->designs->get(Ulid::fromString($designId)));
    }
}
