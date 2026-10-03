<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use Symfony\Component\Uid\Ulid;

interface NotebookScanRepository
{
    public function add(NotebookScan $scan): void;

    public function ofEvent(Ulid $eventId): ?NotebookScan;
}
