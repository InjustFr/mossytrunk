<?php

declare(strict_types=1);

namespace App\Presentation\Api\Reference;

use App\Application\Reference\PreviewReferenceFormat\PreviewReferenceFormatHandler;
use App\Domain\Reference\ReferenceKind;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[Route('/api/references/formats/{kind}/preview', name: 'api_reference_format_preview', requirements: ['kind' => new EnumRequirement(ReferenceKind::class)], methods: ['GET'], format: 'json')]
final class PreviewReferenceFormatController extends AbstractController
{
    public function __invoke(ReferenceKind $kind, PreviewReferenceFormatHandler $preview, #[MapQueryParameter] string $template = ''): JsonResponse
    {
        return $this->json(['example' => $preview($kind, $template)]);
    }
}
