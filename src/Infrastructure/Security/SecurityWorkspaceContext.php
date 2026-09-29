<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\WorkspaceContext;
use App\Domain\Identity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class SecurityWorkspaceContext implements WorkspaceContext
{
    public function __construct(
        private TokenStorageInterface $tokens,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function current(): Workspace
    {
        $user = $this->tokens->getToken()?->getUser();
        if (!$user instanceof SecurityUser) {
            throw new \LogicException('No signed-in user: business data is only reachable inside a workspace.');
        }

        return $this->entityManager->getReference(Workspace::class, $user->workspaceId)
            ?? throw new \LogicException('The signed-in user\'s workspace no longer exists.');
    }
}
