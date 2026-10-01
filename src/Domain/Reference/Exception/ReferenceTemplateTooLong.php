<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class ReferenceTemplateTooLong extends InvalidReferenceTemplate
{
    public function __construct(int $max)
    {
        parent::__construct('reference.template_too_long', ['max' => $max]);
    }
}
