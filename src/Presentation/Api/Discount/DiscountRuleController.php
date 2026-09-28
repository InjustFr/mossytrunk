<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DeleteDiscountRule\DeleteDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Discount\ToggleDiscountRule\ToggleDiscountRuleHandler;
use App\Application\Discount\UpdateDiscountRule\UpdateDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/discount-rules', format: 'json')]
final class DiscountRuleController extends AbstractController
{
    #[Route('', name: 'api_discount_rules_list', methods: ['GET'])]
    public function list(ListDiscountRulesHandler $listRules): JsonResponse
    {
        return $this->json($listRules());
    }

    #[Route('', name: 'api_discount_rules_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] DiscountRulePayload $payload, CreateDiscountRuleHandler $createRule): JsonResponse
    {
        return $this->json(['id' => (string) $createRule($payload->toDefinition())], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_discount_rules_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] DiscountRulePayload $payload, UpdateDiscountRuleHandler $updateRule): Response
    {
        $updateRule($id, $payload->toDefinition());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/activation', name: 'api_discount_rules_activate', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function activate(string $id, ToggleDiscountRuleHandler $toggleRule): Response
    {
        $toggleRule($id, true);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/activation', name: 'api_discount_rules_deactivate', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function deactivate(string $id, ToggleDiscountRuleHandler $toggleRule): Response
    {
        $toggleRule($id, false);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_discount_rules_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function delete(string $id, DeleteDiscountRuleHandler $deleteRule): Response
    {
        $deleteRule($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
