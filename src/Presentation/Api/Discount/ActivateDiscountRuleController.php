<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\StartDiscountRule\StartDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/discount-rules/{id}/activation', name: 'api_discount_rules_activate', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ActivateDiscountRuleController extends AbstractController
{
    public function __invoke(string $id, StartDiscountRuleHandler $startRule): Response
    {
        $startRule($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
