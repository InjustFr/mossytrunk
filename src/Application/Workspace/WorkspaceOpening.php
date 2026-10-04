<?php

declare(strict_types=1);

namespace App\Application\Workspace;

use App\Application\Translator;
use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceRepository;
use App\Domain\Reference\ReferenceFormat;
use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;

final readonly class WorkspaceOpening
{
    public function __construct(
        private WorkspaceRepository $workspaces,
        private SalesChannelRepository $channels,
        private ReferenceFormatRepository $formats,
        private Translator $translator,
    ) {
    }

    public function open(string $name): Workspace
    {
        $workspace = Workspace::create($name);
        $this->workspaces->add($workspace);
        $this->channels->add(SalesChannel::main($workspace, $this->translator->trans('sales.main_channel')));
        foreach (ReferenceKind::cases() as $kind) {
            $this->formats->add(ReferenceFormat::standard($workspace, $kind));
        }

        return $workspace;
    }
}
