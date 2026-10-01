<?php

declare(strict_types=1);

namespace App\Presentation\Api\Reference;

use App\Application\Reference\ChangeReferenceFormat\ChangeReferenceFormat;
use App\Application\Reference\ChangeReferenceFormat\ChangeReferenceFormatHandler;
use App\Domain\Reference\ReferenceKind;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[Route('/api/references/formats/{kind}', name: 'api_reference_format_change', requirements: ['kind' => new EnumRequirement(ReferenceKind::class)], methods: ['PUT'], format: 'json')]
final class ChangeReferenceFormatController extends AbstractController
{
    public function __invoke(ReferenceKind $kind, Request $request, ChangeReferenceFormatHandler $change): JsonResponse
    {
        $payload = $request->getPayload();

        return $this->json(['renamed' => $change(new ChangeReferenceFormat($kind, $payload->getString('template'), $payload->getBoolean('applyToExisting')))]);
    }
}
