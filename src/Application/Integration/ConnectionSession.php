<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Application\Integration\Exception\ServiceNotConfigured;
use App\Application\Integration\Exception\ServiceNotConnected;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnection;
use Psr\Clock\ClockInterface;

final readonly class ConnectionSession
{
    private const string ACCESS_TOKEN = 'access_token';
    private const string REFRESH_TOKEN = 'refresh_token';

    public function __construct(
        private Connectors $connectors,
        private ConnectionSecrets $secrets,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function configured(ServiceConnection $connection): Credentials
    {
        $description = $this->connectors->get($connection->service())->describe();
        $values = $connection->settings();
        foreach ($description->secretFields() as $field) {
            $secret = $this->secrets->reveal($connection, $field->name);
            if (null !== $secret) {
                $values[$field->name] = $secret;
            }
        }
        foreach ($description->fields as $field) {
            if ($field->required && !isset($values[$field->name])) {
                throw new ServiceNotConfigured($description->label);
            }
        }

        return new Credentials($values, accountId: $connection->accountId());
    }

    public function credentials(ServiceConnection $connection): Credentials
    {
        $connector = $this->connectors->get($connection->service());
        $credentials = $this->configured($connection);
        if (!$connector instanceof AuthorizingConnector) {
            return $credentials;
        }

        $access = $this->secrets->reveal($connection, self::ACCESS_TOKEN);
        $refresh = $this->secrets->reveal($connection, self::REFRESH_TOKEN);
        $expiresAt = $connection->tokenExpiresAt();
        if (null === $access || null === $refresh || null === $expiresAt || !$connection->isAuthorized()) {
            throw new ServiceNotConnected($connector->describe()->label);
        }

        $authorized = new Credentials($credentials->values, $access, $refresh, $connection->accountId());
        if ($expiresAt > $this->clock->now()->modify('+1 minute')) {
            return $authorized;
        }

        $tokens = $connector->refresh($authorized);
        $this->keepTokens($connection, $tokens);
        $connection->renewToken($tokens->expiresAt);
        $this->transaction->commit();

        return new Credentials($credentials->values, $tokens->accessToken, $tokens->refreshToken, $connection->accountId());
    }

    public function keepTokens(ServiceConnection $connection, Tokens $tokens): void
    {
        $this->secrets->keep($connection, self::ACCESS_TOKEN, $tokens->accessToken);
        $this->secrets->keep($connection, self::REFRESH_TOKEN, $tokens->refreshToken);
    }

    public function disconnect(ServiceConnection $connection): void
    {
        $this->secrets->forget($connection, self::ACCESS_TOKEN);
        $this->secrets->forget($connection, self::REFRESH_TOKEN);
        $connection->revoke();
    }

    public function forget(ServiceConnection $connection): void
    {
        $this->disconnect($connection);
        foreach ($this->connectors->get($connection->service())->describe()->secretFields() as $field) {
            $this->secrets->forget($connection, $field->name);
        }
    }
}
