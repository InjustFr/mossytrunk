<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\Authorize\AuthorizeHandler;
use App\Application\Integration\ConfigureConnection\AddConnectionHandler;
use App\Application\Integration\ConfigureConnection\UpdateConnectionHandler;
use App\Application\Integration\Connectors;
use App\Application\Integration\ImportSales\ImportSalesHandler;
use App\Application\Integration\LinkExternalItem\LinkExternalItemHandler;
use App\Application\Integration\ListExternalItems\ListExternalItemsHandler;
use App\Application\Integration\ListServices\ListServicesHandler;
use App\Application\Integration\RemoveConnection\RemoveConnectionHandler;
use App\Application\Integration\ServiceDescription;
use App\Domain\Shared\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

#[Route('/api/services', format: 'json')]
final class ServicesController extends AbstractController
{
    private const int IMPORT_TIME_LIMIT_SECONDS = 300;
    private const string SERVICE = '[a-z0-9]{2,32}';

    public function __construct(
        private readonly Connectors $connectors,
        private readonly ConnectionPayload $payload,
    ) {
    }

    #[Route('', name: 'api_services', methods: ['GET'])]
    public function list(ListServicesHandler $services): JsonResponse
    {
        return $this->json($services());
    }

    #[Route('', name: 'api_services_add', methods: ['POST'])]
    public function add(Request $request, AddConnectionHandler $add): Response
    {
        $add($this->payload->settings($request, $this->description($request->getPayload()->getString('service')), true));

        return new Response(status: Response::HTTP_CREATED);
    }

    #[Route('/{service}', name: 'api_services_update', requirements: ['service' => self::SERVICE], methods: ['PUT'])]
    public function update(string $service, Request $request, UpdateConnectionHandler $update): Response
    {
        $update($this->payload->settings($request, $this->description($service), false));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{service}', name: 'api_services_remove', requirements: ['service' => self::SERVICE], methods: ['DELETE'])]
    public function remove(string $service, RemoveConnectionHandler $remove): Response
    {
        $remove($service);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{service}/authorization', name: 'api_services_disconnect', requirements: ['service' => self::SERVICE], methods: ['DELETE'])]
    public function disconnect(string $service, AuthorizeHandler $authorize): Response
    {
        $authorize->disconnect($service);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{service}/import', name: 'api_services_import', requirements: ['service' => self::SERVICE], methods: ['POST'])]
    public function import(string $service, ImportSalesHandler $import): JsonResponse
    {
        set_time_limit(self::IMPORT_TIME_LIMIT_SECONDS);

        return $this->json($import($service));
    }

    #[Route('/{service}/items', name: 'api_services_items', requirements: ['service' => self::SERVICE], methods: ['GET'])]
    public function items(string $service, ListExternalItemsHandler $items): JsonResponse
    {
        return $this->json($items($service));
    }

    #[Route('/{service}/items/{id}', name: 'api_services_items_link', requirements: ['service' => self::SERVICE, 'id' => Requirement::ULID], methods: ['PUT'])]
    public function link(string $service, string $id, Request $request, LinkExternalItemHandler $link): Response
    {
        $payload = $request->getPayload();
        $variant = $payload->getString('variant');
        $productId = $payload->getString('productId');
        if (!Ulid::isValid($productId)) {
            throw new UnprocessableEntityHttpException('Choisissez un produit.');
        }
        $link($service, $id, $productId, '' === $variant ? null : $variant);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function description(string $service): ServiceDescription
    {
        if (!$this->connectors->has($service)) {
            throw new NotFound('Service', $service);
        }

        return $this->connectors->get($service)->describe();
    }
}
