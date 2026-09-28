<?php

declare(strict_types=1);

namespace App\Application\Workspace;

use App\Domain\Identity\InvalidAccount;
use App\Domain\Identity\SecretName;
use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceSecret;
use App\Domain\Identity\WorkspaceSecretRepository;

final readonly class WorkspaceSecrets
{
    public function __construct(
        private WorkspaceSecretRepository $secrets,
        private SecretCipher $cipher,
    ) {
    }

    public function reveal(Workspace $workspace, SecretName $name): ?string
    {
        $secret = $this->secrets->find($workspace, $name);

        return null === $secret ? null : $this->cipher->decrypt($secret->ciphertext());
    }

    public function keep(Workspace $workspace, SecretName $name, string $plaintext): void
    {
        $plaintext = trim($plaintext);
        if ('' === $plaintext) {
            throw InvalidAccount::emptySecret();
        }

        $ciphertext = $this->cipher->encrypt($plaintext);
        $secret = $this->secrets->find($workspace, $name);
        if (null === $secret) {
            $this->secrets->add(WorkspaceSecret::store($workspace, $name, $ciphertext));
        } else {
            $secret->replace($ciphertext);
        }
    }

    public function forget(Workspace $workspace, SecretName $name): void
    {
        $secret = $this->secrets->find($workspace, $name);
        if (null !== $secret) {
            $this->secrets->remove($secret);
        }
    }
}
