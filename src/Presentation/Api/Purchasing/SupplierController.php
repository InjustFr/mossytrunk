<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\ListSuppliers\ListSuppliersHandler;
use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/suppliers', format: 'json')]
final class SupplierController extends AbstractController
{
    #[Route('', name: 'api_suppliers_list', methods: ['GET'])]
    public function list(ListSuppliersHandler $listSuppliers): JsonResponse
    {
        return $this->json($listSuppliers());
    }

    #[Route('', name: 'api_suppliers_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] SupplierPayload $payload, SaveSupplierHandler $saveSupplier): JsonResponse
    {
        return $this->json(SupplierView::of($saveSupplier($payload->toCommand())), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_suppliers_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] SupplierPayload $payload, SaveSupplierHandler $saveSupplier): JsonResponse
    {
        return $this->json(SupplierView::of($saveSupplier($payload->toCommand($id))));
    }
}
