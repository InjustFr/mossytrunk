<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\DeleteChannel\DeleteChannelHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}', name: 'api_sales_channels_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteChannelController extends AbstractController
{
    public function __invoke(string $id, DeleteChannelHandler $deleteChannel): Response
    {
        $deleteChannel($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
