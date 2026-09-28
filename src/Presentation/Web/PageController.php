<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Serves the Twig shells that mount Vue pages. Data is loaded by the pages through the JSON API.
 */
final class PageController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('orders');
    }

    #[Route('/commandes', name: 'orders', methods: ['GET'])]
    public function orders(): Response
    {
        return $this->page('OrdersPage', 'Commandes');
    }

    #[Route('/produits', name: 'products', methods: ['GET'])]
    public function products(): Response
    {
        return $this->page('ProductsPage', 'Produits');
    }

    #[Route('/evenements', name: 'events', methods: ['GET'])]
    public function events(): Response
    {
        return $this->page('EventsPage', 'Événements');
    }

    #[Route('/evenements/{id}', name: 'event_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function event(string $id): Response
    {
        return $this->page('EventDetailPage', 'Événement', ['eventId' => $id]);
    }

    #[Route('/remises', name: 'discounts', methods: ['GET'])]
    public function discounts(): Response
    {
        return $this->page('DiscountsPage', 'Remises');
    }

    /**
     * @param array<string, mixed> $props
     */
    private function page(string $component, string $title, array $props = []): Response
    {
        return $this->render('page.html.twig', [
            'component' => $component,
            'title' => $title,
            'props' => $props,
        ]);
    }
}
