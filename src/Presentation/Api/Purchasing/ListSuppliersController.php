<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\ListSuppliers\ListSuppliersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/suppliers', name: 'api_suppliers_list', methods: ['GET'], format: 'json')]
final class ListSuppliersController extends AbstractController
{
    public function __invoke(ListSuppliersHandler $listSuppliers): JsonResponse
    {
        return $this->json($listSuppliers());
    }
}
