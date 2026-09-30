<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\ReviseSupplierOrder\ReviseSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders/{id}', name: 'api_supplier_orders_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ReviseSupplierOrderController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] SupplierOrderPayload $payload, ReviseSupplierOrderHandler $reviseOrder): Response
    {
        $reviseOrder($id, $payload->toDraft());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
