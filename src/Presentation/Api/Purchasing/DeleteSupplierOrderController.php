<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\DeleteSupplierOrder\DeleteSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders/{id}', name: 'api_supplier_orders_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteSupplierOrderController extends AbstractController
{
    public function __invoke(string $id, DeleteSupplierOrderHandler $deleteOrder): Response
    {
        $deleteOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
