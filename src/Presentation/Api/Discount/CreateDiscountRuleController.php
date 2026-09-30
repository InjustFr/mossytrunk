<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/discount-rules', name: 'api_discount_rules_create', methods: ['POST'], format: 'json')]
final class CreateDiscountRuleController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] DiscountRulePayload $payload, CreateDiscountRuleHandler $createRule): JsonResponse
    {
        return $this->json(['id' => (string) $createRule($payload->toDefinition())], Response::HTTP_CREATED);
    }
}
