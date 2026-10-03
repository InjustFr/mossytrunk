<?php

declare(strict_types=1);

namespace App\Domain\Notebook\Exception;

final class NonPositiveNotebookQuantity extends InvalidNotebook
{
    public function __construct(string $written)
    {
        parent::__construct('notebook.non_positive_quantity', ['written' => $written]);
    }
}
