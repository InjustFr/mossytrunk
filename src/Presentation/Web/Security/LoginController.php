<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use App\Presentation\Web\VuePage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/connexion', name: 'login', methods: ['GET', 'POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly VuePage $page,
        private readonly CsrfTokenManagerInterface $csrfTokens,
        private readonly Flashes $flashes,
    ) {
    }

    public function __invoke(AuthenticationUtils $authentication): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->page->render('LoginPage', 'Connexion', [
            'lastEmail' => $authentication->getLastUsername(),
            'error' => self::loginError($authentication->getLastAuthenticationError()),
            'csrfToken' => $this->csrfTokens->getToken('authenticate')->getValue(),
            'notice' => $this->flashes->take('notice'),
        ]);
    }

    private static function loginError(?AuthenticationException $error): ?string
    {
        return match (true) {
            null === $error => null,
            $error instanceof BadCredentialsException, $error instanceof UserNotFoundException => 'Email ou mot de passe incorrect.',
            $error instanceof TooManyLoginAttemptsAuthenticationException => 'Trop de tentatives : réessayez dans quelques minutes.',
            $error instanceof InvalidCsrfTokenException => 'Votre session a expiré, réessayez.',
            default => 'Connexion impossible, réessayez.',
        };
    }
}
