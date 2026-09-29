<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Integration;

use App\Domain\Integration\InvalidConnection;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\UnknownItems;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServiceConnectionTest extends TestCase
{
    public function testKeepsTrimmedNonEmptySettingsAndTheChosenOptions(): void
    {
        $connection = $this->connection(['merchant_code' => ' MC42 ', 'note' => '  ']);

        self::assertSame(['merchant_code' => 'MC42'], $connection->settings());
        self::assertSame('MC42', $connection->setting('merchant_code'));
        self::assertNull($connection->setting('note'));
        self::assertSame(SalesContext::AtEvent, $connection->salesContext());
        self::assertSame(UnknownItems::CreateProduct, $connection->unknownItems());
    }

    public function testConfigureTellsWhetherTheSettingsChanged(): void
    {
        $connection = $this->connection(['merchant_code' => 'MC42']);

        self::assertFalse($connection->configure(['merchant_code' => 'MC42 ']));
        self::assertTrue($connection->configure(['merchant_code' => 'MC43']));
    }

    public function testOptionsCanBeChosenAgain(): void
    {
        $connection = $this->connection([]);

        $connection->choose(SalesContext::Online, UnknownItems::LinkByHand);

        self::assertSame(SalesContext::Online, $connection->salesContext());
        self::assertSame(UnknownItems::LinkByHand, $connection->unknownItems());
    }

    public function testAuthorizationCanBeRenewedAndRevoked(): void
    {
        $connection = $this->connection([]);
        self::assertFalse($connection->isAuthorized());

        $connection->authorize('12345678', 'Atelier Mousse sur Etsy', new \DateTimeImmutable('2030-01-01 10:00'));
        $connection->renewToken(new \DateTimeImmutable('2030-01-01 11:00'));

        self::assertTrue($connection->isAuthorized());
        self::assertSame(['12345678', 'Atelier Mousse sur Etsy'], [$connection->accountId(), $connection->accountName()]);
        self::assertEquals(new \DateTimeImmutable('2030-01-01 11:00'), $connection->tokenExpiresAt());

        $connection->revoke();

        self::assertFalse($connection->isAuthorized());
        self::assertNull($connection->tokenExpiresAt());
    }

    #[DataProvider('invalidServices')]
    public function testTheServiceKeyIsShortLowercaseAndNotManual(string $service): void
    {
        $this->expectException(InvalidConnection::class);

        ServiceConnection::create(TestWorkspace::get(), $service, [], SalesContext::Online, UnknownItems::LinkByHand);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidServices(): iterable
    {
        yield 'manual' => ['manual'];
        yield 'uppercase' => ['SumUp'];
        yield 'underscore' => ['sum_up'];
        yield 'too short' => ['s'];
    }

    public function testASettingNameIsSnakeCase(): void
    {
        $this->expectException(InvalidConnection::class);

        $this->connection(['Merchant Code' => 'MC42']);
    }

    /**
     * @param array<string, string> $settings
     */
    private function connection(array $settings): ServiceConnection
    {
        return ServiceConnection::create(TestWorkspace::get(), 'sumup', $settings, SalesContext::AtEvent, UnknownItems::CreateProduct);
    }
}
