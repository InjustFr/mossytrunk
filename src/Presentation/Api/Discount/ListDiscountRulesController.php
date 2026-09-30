<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/discount-rules', name: 'api_discount_rules_list', methods: ['GET'], format: 'json')]
final class ListDiscountRulesController extends AbstractController
{
    public function __invoke(ListDiscountRulesHandler $listRules): JsonResponse
    {
        return $this->json($listRules());
    }
}
