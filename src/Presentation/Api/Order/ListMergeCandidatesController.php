<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\ListMergeCandidates\ListMergeCandidatesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/merge-candidates', name: 'api_orders_merge_candidates', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class ListMergeCandidatesController extends AbstractController
{
    public function __invoke(string $id, ListMergeCandidatesHandler $listMergeCandidates): JsonResponse
    {
        return $this->json($listMergeCandidates($id));
    }
}
