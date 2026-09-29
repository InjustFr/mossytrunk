<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use App\Application\Etsy\ConnectEtsy\ConnectEtsyHandler;
use App\Domain\Shared\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EtsyConnectionController extends AbstractController
{
    private const string SESSION_KEY = 'etsy_oauth';

    #[Route('/parametres/etsy/connexion', name: 'etsy_connect', methods: ['GET'])]
    public function connect(Request $request, ConnectEtsyHandler $connect): RedirectResponse
    {
        $verifier = self::base64Url(random_bytes(48));
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set(self::SESSION_KEY, ['state' => $state, 'verifier' => $verifier]);

        try {
            return $this->redirect($connect->authorizationUrl($this->callbackUrl(), $state, self::base64Url(hash('sha256', $verifier, true))));
        } catch (DomainException) {
            return $this->redirect('/parametres?etsy=indisponible');
        }
    }

    #[Route('/parametres/etsy/retour', name: 'etsy_callback', methods: ['GET'])]
    public function callback(Request $request, ConnectEtsyHandler $connect): RedirectResponse
    {
        $pending = $request->getSession()->remove(self::SESSION_KEY);
        $state = \is_array($pending) && \is_string($pending['state'] ?? null) ? $pending['state'] : null;
        $verifier = \is_array($pending) && \is_string($pending['verifier'] ?? null) ? $pending['verifier'] : null;
        $code = $request->query->getString('code');

        if (null === $state || null === $verifier || '' === $code || !hash_equals($state, $request->query->getString('state'))) {
            return $this->redirect('/parametres?etsy=refuse');
        }

        try {
            $connect->complete($code, $verifier, $this->callbackUrl());
        } catch (DomainException) {
            return $this->redirect('/parametres?etsy=erreur');
        }

        return $this->redirect('/parametres?etsy=connecte');
    }

    private function callbackUrl(): string
    {
        return $this->generateUrl('etsy_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
