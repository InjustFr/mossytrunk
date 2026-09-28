<?php

declare(strict_types=1);

namespace App\Infrastructure\Crypto;

use App\Application\Workspace\SecretCipher;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SodiumSecretCipher implements SecretCipher
{
    private const string VERSION = 'v1:';

    public function __construct(
        #[Autowire(env: 'APP_ENCRYPTION_KEY')]
        private string $base64Key,
    ) {
    }

    public static function generateKey(): string
    {
        return base64_encode(sodium_crypto_secretbox_keygen());
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return self::VERSION.base64_encode($nonce.sodium_crypto_secretbox($plaintext, $nonce, $this->key()));
    }

    public function decrypt(string $ciphertext): string
    {
        if (!str_starts_with($ciphertext, self::VERSION)) {
            throw new \RuntimeException('Unknown secret format.');
        }

        $payload = base64_decode(substr($ciphertext, \strlen(self::VERSION)), true);
        if (false === $payload || \strlen($payload) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Corrupted secret.');
        }

        $plaintext = sodium_crypto_secretbox_open(
            substr($payload, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($payload, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key(),
        );
        if (false === $plaintext) {
            throw new \RuntimeException('Secret cannot be decrypted with the current APP_ENCRYPTION_KEY.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $key = base64_decode($this->base64Key, true);
        if (false === $key || \SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== \strlen($key)) {
            throw new \RuntimeException('APP_ENCRYPTION_KEY must be 32 bytes encoded in base64 (php bin/console app:encryption:generate-key).');
        }

        return $key;
    }
}
