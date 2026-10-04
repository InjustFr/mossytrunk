<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\StopDiscountRule\StopDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/discount-rules/{id}/activation', name: 'api_discount_rules_deactivate', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeactivateDiscountRuleController extends AbstractController
{
    public function __invoke(string $id, StopDiscountRuleHandler $stopRule): Response
    {
        $stopRule($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
