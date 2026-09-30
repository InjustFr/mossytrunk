<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/password/set/{token}', name: 'password_set_link', requirements: ['token' => '[0-9a-f]{40,}'], methods: ['GET'])]
final class PasswordLinkController extends AbstractController
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $request->getSession()->set(SetPasswordController::SESSION_TOKEN, $token);

        return $this->redirectToRoute('password_set');
    }
}
