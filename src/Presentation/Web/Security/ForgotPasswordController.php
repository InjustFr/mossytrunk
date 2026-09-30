<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use App\Application\Identity\RequestPasswordReset\RequestPasswordResetHandler;
use App\Presentation\Web\VuePage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/password/forgot', name: 'password_forgot', methods: ['GET', 'POST'])]
final class ForgotPasswordController extends AbstractController
{
    private const string CSRF_ID = 'forgot_password';

    public function __construct(
        private readonly VuePage $page,
        private readonly CsrfTokenManagerInterface $csrfTokens,
        private readonly Flashes $flashes,
    ) {
    }

    public function __invoke(
        Request $request,
        RequestPasswordResetHandler $requestReset,
        RateLimiterFactoryInterface $forgotPasswordLimiter,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));

            if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_csrf_token'))) {
                return $this->form($email, 'Votre session a expiré, réessayez.');
            }

            if ('' !== $email && $forgotPasswordLimiter->create(mb_strtolower($email).'|'.$request->getClientIp())->consume()->isAccepted()) {
                $requestReset($email);
            }
            $this->addFlash('sent', $email);

            return $this->redirectToRoute('password_forgot');
        }

        $sent = $this->flashes->take('sent');

        return $this->form($sent ?? '', null, null !== $sent);
    }

    private function form(string $email, ?string $error = null, bool $sent = false): Response
    {
        return $this->page->render('ForgotPasswordPage', 'Mot de passe oublié', [
            'email' => $email,
            'sent' => $sent,
            'error' => $error,
            'csrfToken' => $this->csrfTokens->getToken(self::CSRF_ID)->getValue(),
        ]);
    }
}
