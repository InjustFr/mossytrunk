<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\RemovePaymentFee\RemovePaymentFeeHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/services/{service}/fees/{feeId}', name: 'api_services_fees_remove', requirements: ['service' => RouteRequirement::SERVICE, 'feeId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RemovePaymentFeeController extends AbstractController
{
    public function __invoke(string $service, string $feeId, RemovePaymentFeeHandler $removeFee): Response
    {
        $removeFee($service, $feeId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
