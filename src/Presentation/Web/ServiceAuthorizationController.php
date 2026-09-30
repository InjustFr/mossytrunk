<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use App\Application\Integration\Authorize\AuthorizeHandler;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ServiceAuthorizationController extends AbstractController
{
    private const string SERVICE = '[a-z0-9]{2,32}';

    #[Route('/parametres/{service}/connexion', name: 'service_connect', requirements: ['service' => self::SERVICE], methods: ['GET'])]
    public function connect(string $service, Request $request, AuthorizeHandler $authorize): RedirectResponse
    {
        $verifier = self::base64Url(random_bytes(48));
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set(self::sessionKey($service), ['state' => $state, 'verifier' => $verifier]);

        try {
            return $this->redirect($authorize->authorizationUrl($service, $this->callbackUrl($service), $state, self::base64Url(hash('sha256', $verifier, true))));
        } catch (DomainException) {
            return $this->outcome($service, 'indisponible');
        }
    }

    #[Route('/parametres/{service}/retour', name: 'service_callback', requirements: ['service' => self::SERVICE], methods: ['GET'])]
    public function callback(string $service, Request $request, AuthorizeHandler $authorize): RedirectResponse
    {
        $pending = $request->getSession()->remove(self::sessionKey($service));
        $state = \is_array($pending) && \is_string($pending['state'] ?? null) ? $pending['state'] : null;
        $verifier = \is_array($pending) && \is_string($pending['verifier'] ?? null) ? $pending['verifier'] : null;
        $code = $request->query->getString('code');

        if (null === $state || null === $verifier || '' === $code || !hash_equals($state, $request->query->getString('state'))) {
            return $this->outcome($service, 'refuse');
        }

        try {
            $authorize->complete($service, $code, $verifier, $this->callbackUrl($service));
        } catch (DomainException) {
            return $this->outcome($service, 'erreur');
        }

        return $this->outcome($service, 'connecte');
    }

    private function outcome(string $service, string $outcome): RedirectResponse
    {
        return $this->redirect('/parametres?'.http_build_query(['service' => $service, 'connexion' => $outcome]));
    }

    private function callbackUrl(string $service): string
    {
        return $this->generateUrl('service_callback', ['service' => $service], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private static function sessionKey(string $service): string
    {
        return 'oauth_'.$service;
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
