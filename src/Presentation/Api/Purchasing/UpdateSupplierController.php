<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/suppliers/{id}', name: 'api_suppliers_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateSupplierController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] SupplierPayload $payload, SaveSupplierHandler $saveSupplier): JsonResponse
    {
        return $this->json(SupplierView::of($saveSupplier($payload->toCommand($id))));
    }
}
