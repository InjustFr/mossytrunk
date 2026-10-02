<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\AddPaymentFee\AddPaymentFeeHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/fees', name: 'api_services_fees_add', requirements: ['service' => RouteRequirement::SERVICE], methods: ['POST'], format: 'json')]
final class AddPaymentFeeController extends AbstractController
{
    public function __invoke(string $service, #[MapRequestPayload] PaymentFeePayload $payload, AddPaymentFeeHandler $addFee): JsonResponse
    {
        return $this->json(['id' => (string) $addFee($service, $payload->paymentMethod(), $payload->label, $payload->kind(), $payload->amount)], Response::HTTP_CREATED);
    }
}
