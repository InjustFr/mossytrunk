<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\LinkExternalItem\LinkExternalItemHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/services/{service}/items/{id}', name: 'api_services_items_link', requirements: ['service' => RouteRequirement::SERVICE, 'id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class LinkExternalItemController extends AbstractController
{
    public function __invoke(string $service, string $id, Request $request, LinkExternalItemHandler $link, TranslatorInterface $translator): Response
    {
        $payload = $request->getPayload();
        $variant = $payload->getString('variant');
        $productId = $payload->getString('productId');
        if (!Ulid::isValid($productId)) {
            throw new UnprocessableEntityHttpException($translator->trans('problem.product_required'));
        }
        $link($service, $id, $productId, '' === $variant ? null : $variant);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
