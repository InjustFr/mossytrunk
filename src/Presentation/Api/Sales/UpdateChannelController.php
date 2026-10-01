<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\SaveChannel\SaveChannelHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}', name: 'api_sales_channels_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateChannelController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ChannelPayload $payload, SaveChannelHandler $saveChannel): Response
    {
        $saveChannel($id, $payload->name, $payload->kind(), $payload->service);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
