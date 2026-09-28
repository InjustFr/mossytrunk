<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Crypto\SodiumSecretCipher;
use PHPUnit\Framework\TestCase;

final class SodiumSecretCipherTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $cipher = new SodiumSecretCipher(SodiumSecretCipher::generateKey());

        $ciphertext = $cipher->encrypt('sup_sk_secret');

        self::assertStringStartsWith('v1:', $ciphertext);
        self::assertStringNotContainsString('sup_sk_secret', $ciphertext);
        self::assertSame('sup_sk_secret', $cipher->decrypt($ciphertext));
    }

    public function testSameSecretEncryptsDifferentlyEachTime(): void
    {
        $cipher = new SodiumSecretCipher(SodiumSecretCipher::generateKey());

        self::assertNotSame($cipher->encrypt('sup_sk_secret'), $cipher->encrypt('sup_sk_secret'));
    }

    public function testTamperedCiphertextIsRejected(): void
    {
        $cipher = new SodiumSecretCipher(SodiumSecretCipher::generateKey());
        $payload = base64_decode(substr($cipher->encrypt('sup_sk_secret'), 3));
        $payload[-1] = \chr(\ord($payload[-1]) ^ 1);

        $this->expectException(\RuntimeException::class);
        $cipher->decrypt('v1:'.base64_encode($payload));
    }

    public function testAnotherKeyCannotDecrypt(): void
    {
        $ciphertext = (new SodiumSecretCipher(SodiumSecretCipher::generateKey()))->encrypt('sup_sk_secret');

        $this->expectException(\RuntimeException::class);
        (new SodiumSecretCipher(SodiumSecretCipher::generateKey()))->decrypt($ciphertext);
    }

    public function testRejectsAMalformedKey(): void
    {
        $this->expectException(\RuntimeException::class);
        (new SodiumSecretCipher('too-short'))->encrypt('sup_sk_secret');
    }
}
