<?php

declare(strict_types=1);

namespace App\Application\Etsy;

use App\Application\Transaction;
use App\Application\Workspace\WorkspaceSecrets;
use App\Domain\Identity\SecretName;
use App\Domain\Identity\Workspace;
use Psr\Clock\ClockInterface;

final readonly class EtsySession
{
    public function __construct(
        private EtsyGateway $etsy,
        private WorkspaceSecrets $secrets,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function app(Workspace $workspace): EtsyApp
    {
        $keystring = $workspace->etsyKeystring();
        $sharedSecret = $this->secrets->reveal($workspace, SecretName::EtsySharedSecret);
        if (null === $keystring || null === $sharedSecret) {
            throw EtsyUnavailable::notConfigured();
        }

        return new EtsyApp($keystring, $sharedSecret);
    }

    public function accessToken(Workspace $workspace): string
    {
        $access = $this->secrets->reveal($workspace, SecretName::EtsyAccessToken);
        $refresh = $this->secrets->reveal($workspace, SecretName::EtsyRefreshToken);
        $expiresAt = $workspace->etsyTokenExpiresAt();
        if (null === $access || null === $refresh || null === $expiresAt || null === $workspace->etsyShopId()) {
            throw EtsyUnavailable::notConnected();
        }
        if ($expiresAt > $this->clock->now()->modify('+1 minute')) {
            return $access;
        }

        $tokens = $this->etsy->refresh($this->app($workspace), $refresh);
        $this->keep($workspace, $tokens);
        $workspace->renewEtsyToken($tokens->expiresAt);
        $this->transaction->commit();

        return $tokens->accessToken;
    }

    public function forget(Workspace $workspace): void
    {
        $this->secrets->forget($workspace, SecretName::EtsyAccessToken);
        $this->secrets->forget($workspace, SecretName::EtsyRefreshToken);
        $workspace->disconnectEtsy();
    }

    public function keep(Workspace $workspace, EtsyTokens $tokens): void
    {
        $this->secrets->keep($workspace, SecretName::EtsyAccessToken, $tokens->accessToken);
        $this->secrets->keep($workspace, SecretName::EtsyRefreshToken, $tokens->refreshToken);
    }
}
