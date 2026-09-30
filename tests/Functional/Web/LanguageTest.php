<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Domain\Identity\Language;
use App\Domain\Identity\User;
use App\Infrastructure\Security\SecurityUser;
use App\Presentation\LocaleListener;
use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

final class LanguageTest extends WebTestCase
{
    use SignsInClient;

    public function testPageTitleFollowsTheAcceptedLanguage(): void
    {
        $client = self::signedInClient();

        $client->request('GET', '/products', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);
        self::assertPageTitleSame('Produits · MossyTrunk');

        $client->request('GET', '/products', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.9']);
        self::assertPageTitleSame('Products · MossyTrunk');
    }

    public function testValidationMessagesFollowTheAcceptedLanguage(): void
    {
        $client = self::signedInClient();

        self::assertSame('Le nom du type est obligatoire.', self::violation($client, 'fr'));
        self::assertSame('The type name is required.', self::violation($client, 'en'));
    }

    public function testLocaleCookieWinsOverTheAcceptedLanguage(): void
    {
        $client = self::signedInClient();
        $client->getCookieJar()->set(new Cookie(LocaleListener::COOKIE, 'fr'));

        $client->request('GET', '/products', server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);

        self::assertPageTitleSame('Produits · MossyTrunk');
    }

    public function testSavedLanguageWinsOverCookieAndAcceptedLanguage(): void
    {
        $client = self::createClient();
        $user = self::createMember();
        $user->speak(Language::French);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser(SecurityUser::fromUser($user));
        $client->getCookieJar()->set(new Cookie(LocaleListener::COOKIE, 'en'));

        $client->request('GET', '/products', server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);

        self::assertPageTitleSame('Produits · MossyTrunk');
    }

    public function testChooseLanguageStoresItForTheSignedInUser(): void
    {
        $client = self::createClient();
        $user = self::createMember();
        $client->loginUser(SecurityUser::fromUser($user));

        $client->jsonRequest('PUT', '/api/me/language', ['language' => 'fr']);
        self::assertResponseStatusCodeSame(204);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertSame(Language::French, $entityManager->find(User::class, $user->id())?->language());

        $client->request('GET', '/products');
        self::assertPageTitleSame('Produits · MossyTrunk');
    }

    public function testChooseLanguageRejectsAnUnknownLanguage(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('PUT', '/api/me/language', ['language' => 'de'], ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Langue inconnue.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'violations', 0, 'title'));
    }

    public function testSignedOutApiAnswerFollowsTheAcceptedLanguage(): void
    {
        $client = self::createClient();

        $client->jsonRequest('GET', '/api/products', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Votre session a expiré, reconnectez-vous.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));
    }

    public function testCrossSiteRefusalFollowsTheLocaleCookie(): void
    {
        $client = self::signedInClient();
        $client->getCookieJar()->set(new Cookie(LocaleListener::COOKIE, 'fr'));

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print'], ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ACCEPT_LANGUAGE' => 'en']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame('Requête refusée', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'title'));
    }

    private static function violation(KernelBrowser $client, string $language): string
    {
        $client->jsonRequest('POST', '/api/product-types', ['name' => ''], ['HTTP_ACCEPT_LANGUAGE' => $language]);
        self::assertResponseStatusCodeSame(422);

        return Json::string(Json::decode((string) $client->getResponse()->getContent()), 'violations', 0, 'title');
    }
}
