<?php

declare(strict_types=1);

namespace App\Application\Etsy\ConnectEtsy;

use App\Application\Etsy\EtsyGateway;
use App\Application\Etsy\EtsySession;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Identity\WorkspaceRepository;

final readonly class ConnectEtsyHandler
{
    public function __construct(
        private EtsyGateway $etsy,
        private EtsySession $session,
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private Transaction $transaction,
    ) {
    }

    public function authorizationUrl(string $redirectUri, string $state, string $codeChallenge): string
    {
        return $this->etsy->authorizationUrl($this->session->app($this->workspace->current()), $redirectUri, $state, $codeChallenge);
    }

    public function complete(string $code, string $codeVerifier, string $redirectUri): string
    {
        $workspace = $this->workspaces->get($this->workspace->current()->id());
        $app = $this->session->app($workspace);
        $tokens = $this->etsy->exchangeCode($app, $code, $codeVerifier, $redirectUri);
        $shop = $this->etsy->shop($app, $tokens->accessToken);

        $this->session->keep($workspace, $tokens);
        $workspace->connectEtsy($shop->id, $shop->name, $tokens->expiresAt);
        $this->transaction->commit();

        return $shop->name;
    }
}
