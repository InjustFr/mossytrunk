<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use App\Application\Identity\CheckPasswordToken\CheckPasswordTokenHandler;
use App\Application\Identity\SetPassword\SetPasswordHandler;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Shared\Exception\DomainException;
use App\Presentation\Web\VuePage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/password/set', name: 'password_set', methods: ['GET', 'POST'])]
final class SetPasswordController extends AbstractController
{
    public const string SESSION_TOKEN = 'password_token';
    private const string CSRF_ID = 'set_password';

    public function __construct(
        private readonly VuePage $page,
        private readonly CsrfTokenManagerInterface $csrfTokens,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request, CheckPasswordTokenHandler $check, SetPasswordHandler $setPassword): Response
    {
        $session = $request->getSession();
        $token = $session->get(self::SESSION_TOKEN, '');
        $token = \is_string($token) ? $token : '';

        try {
            $purpose = $check($token);
        } catch (DomainException $exception) {
            return $this->form(null, $this->explain($exception));
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');

            if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_csrf_token'))) {
                return $this->form($purpose, null, $this->translator->trans('session.expired'));
            }
            if ($password !== (string) $request->request->get('confirmation')) {
                return $this->form($purpose, null, $this->translator->trans('password.mismatch'));
            }

            try {
                $setPassword($token, $password);
            } catch (DomainException $exception) {
                return $this->form($purpose, null, $this->explain($exception));
            }

            $session->remove(self::SESSION_TOKEN);
            $this->addFlash('notice', 'password.saved');

            return $this->redirectToRoute('login');
        }

        return $this->form($purpose);
    }

    private function explain(DomainException $exception): string
    {
        return $this->translator->trans($exception->getMessage(), $exception->parameters(), 'exceptions');
    }

    private function form(?PasswordTokenPurpose $purpose, ?string $linkError = null, ?string $error = null): Response
    {
        return $this->page->render('SetPasswordPage', 'password', [
            'invitation' => PasswordTokenPurpose::Invitation === $purpose,
            'linkError' => $linkError,
            'error' => $error,
            'minLength' => SetPasswordHandler::MIN_LENGTH,
            'csrfToken' => $this->csrfTokens->getToken(self::CSRF_ID)->getValue(),
        ], null === $linkError ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}
