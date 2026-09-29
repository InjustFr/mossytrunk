<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Integration;

use App\Application\Integration\Authorize\AuthorizeHandler;
use App\Application\Integration\ConfigureConnection\AddConnectionHandler;
use App\Application\Integration\ConfigureConnection\ConnectionSettings;
use App\Application\Integration\ConfigureConnection\UpdateConnectionHandler;
use App\Application\Integration\ListServices\ListServicesHandler;
use App\Application\Integration\ListServices\ServiceView;
use App\Application\Integration\RemoveConnection\RemoveConnectionHandler;
use App\Application\Integration\ServiceUnavailable;
use App\Domain\Integration\InvalidConnection;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\ExternalSales;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ServiceConnectionUseCasesTest extends KernelTestCase
{
    use ActsAsUser;

    protected function setUp(): void
    {
        self::actAsMemberOf('Atelier A');
    }

    public function testEveryServiceIsListedWithItsFieldsAndNoneIsAddedAtFirst(): void
    {
        $services = $this->services();

        self::assertSame(['etsy', 'sumup'], array_keys($services));
        self::assertSame(['merchant_code', 'api_key'], array_column($services['sumup']->fields, 'name'));
        self::assertSame([false, true], array_column($services['sumup']->fields, 'secret'));
        self::assertSame([false, true], [$services['sumup']->authorizes, $services['etsy']->authorizes]);
        self::assertNull($services['sumup']->connection);
    }

    public function testAddingAServiceUsesItsDefaultOptionsAndKeepsTheSecretEncrypted(): void
    {
        $this->addSumUp('mcode42', 'sup_sk_abcdef1234');

        $sumUp = $this->services()['sumup']->connection;
        self::assertNotNull($sumUp);
        self::assertSame('MCODE42', $sumUp->values['merchant_code']->value);
        self::assertSame([null, true, '••••1234'], [$sumUp->values['api_key']->value, $sumUp->values['api_key']->configured, $sumUp->values['api_key']->hint]);
        self::assertSame(['at_event', 'create_product', true], [$sumUp->salesContext, $sumUp->unknownItems, $sumUp->configured]);

        $stored = self::getContainer()->get(Connection::class)->fetchOne("SELECT ciphertext FROM workspace_secret WHERE name = 'sumup_api_key'");
        self::assertIsString($stored);
        self::assertStringStartsWith('v1:', $stored);
        self::assertStringNotContainsString('sup_sk_abcdef1234', $stored);
    }

    public function testAServiceIsAddedOnlyOnce(): void
    {
        $this->addSumUp('MCODE', 'sup_sk_first0001');

        $this->expectExceptionObject(InvalidConnection::alreadyAdded('SumUp'));

        $this->addSumUp('MCODE', 'sup_sk_first0001');
    }

    public function testEveryRequiredFieldIsNeededToAddAService(): void
    {
        $this->expectException(ServiceUnavailable::class);

        $this->addSumUp('MCODE', '');
    }

    public function testAnUnknownServiceCannotBeAdded(): void
    {
        $this->expectExceptionObject(ServiceUnavailable::unknown('paypal'));

        ExternalSales::connect(self::getContainer()->get(AddConnectionHandler::class), 'paypal', []);
    }

    public function testABlankSecretKeepsTheCurrentOneAndOptionsCanChange(): void
    {
        $this->addSumUp('MCODE', 'sup_sk_first0001');

        $this->update('sumup', ['merchant_code' => 'OTHER', 'api_key' => ''], SalesContext::Online, UnknownItems::LinkByHand);

        $sumUp = $this->services()['sumup']->connection;
        self::assertNotNull($sumUp);
        self::assertSame(['OTHER', '••••0001'], [$sumUp->values['merchant_code']->value, $sumUp->values['api_key']->hint]);
        self::assertSame(['online', 'link_by_hand'], [$sumUp->salesContext, $sumUp->unknownItems]);
    }

    public function testChangingTheAccessOfAnAuthorizedServiceDisconnectsIt(): void
    {
        ExternalSales::connect(self::getContainer()->get(AddConnectionHandler::class), 'etsy', ['keystring' => 'keystring123', 'shared_secret' => 'shared-secret']);
        self::getContainer()->get(AuthorizeHandler::class)->complete('etsy', 'code', 'verifier', 'https://app.test/parametres/etsy/retour');

        $this->update('etsy', ['keystring' => 'keystring123', 'shared_secret' => '']);
        self::assertTrue($this->services()['etsy']->connection?->authorized);

        $this->update('etsy', ['keystring' => 'otherapp456', 'shared_secret' => '']);
        self::assertFalse($this->services()['etsy']->connection?->authorized);
    }

    public function testRemovingAServiceForgetsItsSecrets(): void
    {
        $this->addSumUp('MCODE', 'sup_sk_first0001');

        self::getContainer()->get(RemoveConnectionHandler::class)('sumup');

        self::assertNull($this->services()['sumup']->connection);
        self::assertSame(0, self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM workspace_secret'));
    }

    public function testConnectionsBelongToTheWorkspace(): void
    {
        $this->addSumUp('MCODE', 'sup_sk_first0001');

        self::actAsMemberOf('Atelier B');

        self::assertNull($this->services()['sumup']->connection);
        $this->addSumUp('BCODE', 'sup_sk_other0002');
        self::assertSame('BCODE', $this->services()['sumup']->connection?->values['merchant_code']->value);
    }

    private function addSumUp(string $merchantCode, string $apiKey): void
    {
        ExternalSales::connect(self::getContainer()->get(AddConnectionHandler::class), 'sumup', ['merchant_code' => $merchantCode, 'api_key' => $apiKey]);
    }

    /**
     * @param array<string, string> $fields
     */
    private function update(string $service, array $fields, ?SalesContext $salesContext = null, ?UnknownItems $unknownItems = null): void
    {
        self::getContainer()->get(UpdateConnectionHandler::class)(new ConnectionSettings($service, $fields, $salesContext, $unknownItems));
    }

    /**
     * @return array<string, ServiceView>
     */
    private function services(): array
    {
        $services = [];
        foreach (self::getContainer()->get(ListServicesHandler::class)() as $service) {
            $services[$service->key] = $service;
        }

        return $services;
    }
}
