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
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/tableau-de-bord', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->page('DashboardPage', 'Carnet de bord');
    }

    #[Route('/commandes', name: 'orders', methods: ['GET'])]
    public function orders(): Response
    {
        return $this->page('OrdersPage', 'Commandes');
    }

    #[Route('/commandes/{id}', name: 'order_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function order(string $id): Response
    {
        return $this->page('OrderDetailPage', 'Commande', ['orderId' => $id]);
    }

    #[Route('/produits', name: 'products', methods: ['GET'])]
    public function products(): Response
    {
        return $this->page('ProductsPage', 'Produits');
    }

    #[Route('/produits/{id}', name: 'product_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function product(string $id): Response
    {
        return $this->page('ProductDetailPage', 'Produit', ['productId' => $id]);
    }

    #[Route('/evenements', name: 'events', methods: ['GET'])]
    public function events(): Response
    {
        return $this->page('EventsPage', 'Marchés & salons');
    }

    #[Route('/evenements/{id}', name: 'event_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function event(string $id): Response
    {
        return $this->page('EventDetailPage', 'Événement', ['eventId' => $id]);
    }

    #[Route('/evenements/{id}/inventaire', name: 'event_stock_check', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function stockCheck(string $id): Response
    {
        return $this->page('StockCheckPage', 'Inventaire', ['eventId' => $id]);
    }

    #[Route('/commandes-fournisseurs', name: 'supplier_orders', methods: ['GET'])]
    public function supplierOrders(): Response
    {
        return $this->page('SupplierOrdersPage', 'Commandes fournisseurs');
    }

    #[Route('/commandes-fournisseurs/{id}', name: 'supplier_order_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function supplierOrder(string $id): Response
    {
        return $this->page('SupplierOrderDetailPage', 'Commande fournisseur', ['orderId' => $id]);
    }

    #[Route('/creations', name: 'designs', methods: ['GET'])]
    public function designs(): Response
    {
        return $this->page('DesignsPage', 'Créations');
    }

    #[Route('/creations/{id}', name: 'design_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function design(string $id): Response
    {
        return $this->page('DesignDetailPage', 'Design', ['designId' => $id]);
    }

    #[Route('/comptabilite', name: 'accounting', methods: ['GET'])]
    public function accounting(): Response
    {
        return $this->page('AccountingPage', 'Comptabilité');
    }

    #[Route('/remises', name: 'discounts', methods: ['GET'])]
    public function discounts(): Response
    {
        return $this->page('DiscountsPage', 'Remises');
    }

    #[Route('/parametres', name: 'settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->page('SettingsPage', 'Paramètres');
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
