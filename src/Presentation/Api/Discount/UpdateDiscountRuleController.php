<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\UpdateDiscountRule\UpdateDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/discount-rules/{id}', name: 'api_discount_rules_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateDiscountRuleController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] DiscountRulePayload $payload, UpdateDiscountRuleHandler $updateRule): Response
    {
        $updateRule($id, $payload->toDefinition());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
