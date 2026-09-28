<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Workspace;

use App\Application\Workspace\GetSettings\GetWorkspaceSettingsHandler;
use App\Application\Workspace\RemoveSumUpApiKey\RemoveSumUpApiKeyHandler;
use App\Application\Workspace\UpdateSumUpSettings\UpdateSumUpSettings;
use App\Application\Workspace\UpdateSumUpSettings\UpdateSumUpSettingsHandler;
use App\Domain\Identity\InvalidAccount;
use App\Tests\Support\ActsAsUser;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkspaceSettingsTest extends KernelTestCase
{
    use ActsAsUser;

    protected function setUp(): void
    {
        self::actAsMemberOf('Atelier A');
    }

    public function testApiKeyIsStoredEncryptedAndOnlyHinted(): void
    {
        $this->update(new UpdateSumUpSettings('mcode42', 'sup_sk_abcdef1234'));

        $sumUp = self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->sumUp;
        self::assertSame('MCODE42', $sumUp->merchantCode);
        self::assertTrue($sumUp->apiKeyConfigured);
        self::assertSame('••••1234', $sumUp->apiKeyHint);

        $stored = (string) self::getContainer()->get(Connection::class)->fetchOne('SELECT ciphertext FROM workspace_secret');
        self::assertStringStartsWith('v1:', $stored);
        self::assertStringNotContainsString('sup_sk_abcdef1234', $stored);
    }

    public function testEmptyApiKeyKeepsTheCurrentOne(): void
    {
        $this->update(new UpdateSumUpSettings('MCODE', 'sup_sk_first0001'));

        $this->update(new UpdateSumUpSettings('OTHER', ''));

        $sumUp = self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->sumUp;
        self::assertSame('OTHER', $sumUp->merchantCode);
        self::assertSame('••••0001', $sumUp->apiKeyHint);
    }

    public function testApiKeyCanBeRemoved(): void
    {
        $this->update(new UpdateSumUpSettings('MCODE', 'sup_sk_first0001'));

        self::getContainer()->get(RemoveSumUpApiKeyHandler::class)();

        self::assertFalse(self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->sumUp->apiKeyConfigured);
    }

    public function testMerchantCodeIsValidated(): void
    {
        $this->expectException(InvalidAccount::class);

        $this->update(new UpdateSumUpSettings('not valid!'));
    }

    public function testSettingsBelongToTheWorkspace(): void
    {
        $this->update(new UpdateSumUpSettings('MCODE', 'sup_sk_first0001'));

        self::actAsMemberOf('Atelier B');

        $sumUp = self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->sumUp;
        self::assertNull($sumUp->merchantCode);
        self::assertFalse($sumUp->apiKeyConfigured);
    }

    private function update(UpdateSumUpSettings $command): void
    {
        self::getContainer()->get(UpdateSumUpSettingsHandler::class)($command);
    }
}
