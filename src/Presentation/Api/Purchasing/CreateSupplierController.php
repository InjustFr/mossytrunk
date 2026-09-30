<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/suppliers', name: 'api_suppliers_create', methods: ['POST'], format: 'json')]
final class CreateSupplierController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] SupplierPayload $payload, SaveSupplierHandler $saveSupplier): JsonResponse
    {
        return $this->json(SupplierView::of($saveSupplier($payload->toCommand())), Response::HTTP_CREATED);
    }
}
