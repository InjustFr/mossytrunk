<?php

declare(strict_types=1);

namespace App\Presentation\Api\Etsy;

use App\Application\Etsy\DisconnectEtsy\DisconnectEtsyHandler;
use App\Application\Etsy\ImportFromEtsy\ImportFromEtsyHandler;
use App\Application\Etsy\LinkEtsyListing\LinkEtsyListingHandler;
use App\Application\Etsy\ListEtsyListings\ListEtsyListingsHandler;
use App\Application\Etsy\UpdateEtsySettings\UpdateEtsySettingsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

#[Route('/api/etsy', format: 'json')]
final class EtsyController extends AbstractController
{
    #[Route('/import', name: 'api_etsy_import', methods: ['POST'])]
    public function import(ImportFromEtsyHandler $import): JsonResponse
    {
        set_time_limit(300);

        return $this->json($import());
    }

    #[Route('/listings', name: 'api_etsy_listings', methods: ['GET'])]
    public function listings(ListEtsyListingsHandler $listings): JsonResponse
    {
        return $this->json($listings());
    }

    #[Route('/listings/{id}', name: 'api_etsy_listings_link', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function link(string $id, Request $request, LinkEtsyListingHandler $link): Response
    {
        $payload = $request->getPayload();
        $variant = $payload->getString('variant');
        $productId = $payload->getString('productId');
        if (!Ulid::isValid($productId)) {
            throw new UnprocessableEntityHttpException('Choisissez un produit.');
        }
        $link($id, $productId, '' === $variant ? null : $variant);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/settings', name: 'api_etsy_settings', methods: ['PUT'])]
    public function settings(#[MapRequestPayload] EtsySettingsPayload $payload, UpdateEtsySettingsHandler $update): Response
    {
        $update($payload->keystring, $payload->sharedSecret);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/connection', name: 'api_etsy_disconnect', methods: ['DELETE'])]
    public function disconnect(DisconnectEtsyHandler $disconnect): Response
    {
        $disconnect();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
