<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\Variants\RenameTypeVariantHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types/{id}/variant-renaming', name: 'api_product_types_rename_variant', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class RenameTypeVariantController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] RenameTypeVariantPayload $payload, RenameTypeVariantHandler $renameVariant): Response
    {
        $renameVariant($id, $payload->from, $payload->to);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
