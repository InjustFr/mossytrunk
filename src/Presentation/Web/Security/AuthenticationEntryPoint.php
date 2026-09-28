<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return new JsonResponse(
                ['title' => 'Non connecté', 'status' => Response::HTTP_UNAUTHORIZED, 'detail' => 'Votre session a expiré, reconnectez-vous.'],
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/problem+json'],
            );
        }

        return new RedirectResponse($this->urls->generate('login'));
    }
}
