<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\RevisePaymentFee\RevisePaymentFeeHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/services/{service}/fees/{feeId}', name: 'api_services_fees_revise', requirements: ['service' => RouteRequirement::SERVICE, 'feeId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class RevisePaymentFeeController extends AbstractController
{
    public function __invoke(string $service, string $feeId, #[MapRequestPayload] PaymentFeePayload $payload, RevisePaymentFeeHandler $reviseFee): Response
    {
        $reviseFee($service, $feeId, $payload->paymentMethod(), $payload->label, $payload->kind(), $payload->amount);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
