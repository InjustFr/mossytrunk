<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use App\Application\Identity\CheckPasswordToken\CheckPasswordTokenHandler;
use App\Application\Identity\RequestPasswordReset\RequestPasswordResetHandler;
use App\Application\Identity\SetPassword\SetPasswordHandler;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    private const string SESSION_TOKEN = 'password_token';

    #[Route('/connexion', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authentication): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->authPage('LoginPage', 'Connexion', [
            'lastEmail' => $authentication->getLastUsername(),
            'error' => self::loginError($authentication->getLastAuthenticationError()),
            'csrfToken' => $this->csrfToken('authenticate'),
            'notice' => $this->flash('notice'),
        ]);
    }

    #[Route('/deconnexion', name: 'logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall logout listener.');
    }

    #[Route('/mot-de-passe/oublie', name: 'password_forgot', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        RequestPasswordResetHandler $requestReset,
        RateLimiterFactoryInterface $forgotPasswordLimiter,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));

            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
                return $this->forgotPasswordPage($email, 'Votre session a expiré, réessayez.');
            }

            if ('' !== $email && $forgotPasswordLimiter->create(mb_strtolower($email).'|'.$request->getClientIp())->consume()->isAccepted()) {
                $requestReset($email);
            }
            $this->addFlash('sent', $email);

            return $this->redirectToRoute('password_forgot');
        }

        $sent = $this->flash('sent');

        return $this->forgotPasswordPage($sent ?? '', null, null !== $sent);
    }

    #[Route('/mot-de-passe/definir/{token}', name: 'password_set_link', requirements: ['token' => '[0-9a-f]{40,}'], methods: ['GET'])]
    public function passwordLink(Request $request, string $token): RedirectResponse
    {
        $request->getSession()->set(self::SESSION_TOKEN, $token);

        return $this->redirectToRoute('password_set');
    }

    #[Route('/mot-de-passe/definir', name: 'password_set', methods: ['GET', 'POST'])]
    public function setPassword(Request $request, CheckPasswordTokenHandler $check, SetPasswordHandler $setPassword): Response
    {
        $session = $request->getSession();
        $token = $session->get(self::SESSION_TOKEN, '');
        $token = \is_string($token) ? $token : '';

        try {
            $purpose = $check($token);
        } catch (DomainException $exception) {
            return $this->setPasswordPage(null, $exception->getMessage());
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');

            if (!$this->isCsrfTokenValid('set_password', (string) $request->request->get('_csrf_token'))) {
                return $this->setPasswordPage($purpose, null, 'Votre session a expiré, réessayez.');
            }
            if ($password !== (string) $request->request->get('confirmation')) {
                return $this->setPasswordPage($purpose, null, 'Les deux mots de passe ne correspondent pas.');
            }

            try {
                $setPassword($token, $password);
            } catch (DomainException $exception) {
                return $this->setPasswordPage($purpose, null, $exception->getMessage());
            }

            $session->remove(self::SESSION_TOKEN);
            $this->addFlash('notice', 'Mot de passe enregistré : vous pouvez vous connecter.');

            return $this->redirectToRoute('login');
        }

        return $this->setPasswordPage($purpose);
    }

    private function forgotPasswordPage(string $email, ?string $error = null, bool $sent = false): Response
    {
        return $this->authPage('ForgotPasswordPage', 'Mot de passe oublié', [
            'email' => $email,
            'sent' => $sent,
            'error' => $error,
            'csrfToken' => $this->csrfToken('forgot_password'),
        ]);
    }

    private function setPasswordPage(?PasswordTokenPurpose $purpose, ?string $linkError = null, ?string $error = null): Response
    {
        return $this->authPage('SetPasswordPage', 'Mot de passe', [
            'invitation' => PasswordTokenPurpose::Invitation === $purpose,
            'linkError' => $linkError,
            'error' => $error,
            'minLength' => SetPasswordHandler::MIN_LENGTH,
            'csrfToken' => $this->csrfToken('set_password'),
        ], null === $linkError ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
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

    private function csrfToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }

    private function flash(string $type): ?string
    {
        $session = $this->container->get('request_stack')->getSession();
        $messages = $session instanceof FlashBagAwareSessionInterface ? $session->getFlashBag()->get($type) : [];

        $message = $messages[0] ?? null;

        return \is_string($message) ? $message : null;
    }

    /** @param array<string, mixed> $props */
    private function authPage(string $component, string $title, array $props, int $status = Response::HTTP_OK): Response
    {
        return $this->render('page.html.twig', [
            'component' => $component,
            'title' => $title,
            'props' => $props,
        ], new Response(status: $status));
    }
}
