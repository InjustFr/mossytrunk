<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\DeleteDiscountRule\DeleteDiscountRuleHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/discount-rules/{id}', name: 'api_discount_rules_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteDiscountRuleController extends AbstractController
{
    public function __invoke(string $id, DeleteDiscountRuleHandler $deleteRule): Response
    {
        $deleteRule($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
