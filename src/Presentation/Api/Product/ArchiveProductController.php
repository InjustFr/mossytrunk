<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\ArchiveProduct\ArchiveProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/archive', name: 'api_products_archive', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ArchiveProductController extends AbstractController
{
    public function __invoke(string $id, ArchiveProductHandler $archiveProduct): Response
    {
        $archiveProduct($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
