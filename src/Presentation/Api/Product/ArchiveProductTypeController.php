<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\ArchiveProductType\ArchiveProductTypeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types/{id}/archive', name: 'api_product_types_archive', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ArchiveProductTypeController extends AbstractController
{
    public function __invoke(string $id, ArchiveProductTypeHandler $archiveType): Response
    {
        $archiveType($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
