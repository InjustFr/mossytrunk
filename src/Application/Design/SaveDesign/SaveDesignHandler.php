<?php

declare(strict_types=1);

namespace App\Application\Design\SaveDesign;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use App\Domain\Design\GabaritRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SaveDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
        private GabaritRepository $gabarits,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(SaveDesign $command): Ulid
    {
        $collection = null === $command->collectionId ? null : $this->collections->get(Ulid::fromString($command->collectionId));

        if (null === $command->designId) {
            $design = Design::start($this->workspace->current(), $command->name, $collection, $command->notes);
            foreach ($command->gabaritIds as $gabaritId) {
                $design->decline($this->gabarits->get(Ulid::fromString($gabaritId)));
            }
            $this->designs->add($design);
        } else {
            $design = $this->designs->get(Ulid::fromString($command->designId));
            $design->describe($command->name, $collection, $command->notes);
        }

        $this->transaction->commit();

        return $design->id();
    }
}
