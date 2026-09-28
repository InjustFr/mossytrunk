<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Application\Identity\CreateUser\CreateUser;
use App\Application\Identity\CreateUser\CreateUserHandler;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

final class AuthenticationTest extends WebTestCase
{
    use ActsAsUser;

    public function testAnonymousPagesRedirectToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/produits');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousApiCallsAreUnauthorized(): void
    {
        $client = self::createClient();

        $client->jsonRequest('GET', '/api/products');

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvitedUserSetsPasswordThenSignsIn(): void
    {
        $client = self::createClient();
        self::getContainer()->get(CreateUserHandler::class)(new CreateUser('louis@example.com', 'Atelier'));
        $link = $this->linkFromLastEmail();

        $client->request('GET', $link);
        self::assertResponseRedirects('/mot-de-passe/definir');
        $client->request('GET', '/mot-de-passe/definir');
        self::assertSame(true, self::props($client)['invitation']);

        $client->request('POST', '/mot-de-passe/definir', ['_csrf_token' => self::props($client)['csrfToken'], 'password' => 'correct horse battery', 'confirmation' => 'correct horse battery']);
        self::assertResponseRedirects('/connexion');

        $this->signIn($client, 'Louis@example.com', 'correct horse battery');
        self::assertResponseRedirects('/tableau-de-bord');
        $client->jsonRequest('GET', '/api/products');
        self::assertResponseIsSuccessful();
    }

    public function testWrongPasswordIsRejected(): void
    {
        $client = self::createClient();
        self::getContainer()->get(CreateUserHandler::class)(new CreateUser('louis@example.com', 'Atelier'));

        $this->signIn($client, 'louis@example.com', 'wrong password');
        $client->followRedirect();

        self::assertSame('Email ou mot de passe incorrect.', self::props($client)['error']);
    }

    public function testUsedLinkShowsAnError(): void
    {
        $client = self::createClient();

        $client->request('GET', '/mot-de-passe/definir/'.str_repeat('a', 72));
        $client->followRedirect();

        self::assertResponseStatusCodeSame(400);
        self::assertSame('Ce lien n\'est pas valide.', self::props($client)['linkError']);
    }

    public function testForgotPasswordNeverRevealsAccounts(): void
    {
        $client = self::createClient();
        self::getContainer()->get(CreateUserHandler::class)(new CreateUser('louis@example.com', 'Atelier'));

        foreach (['louis@example.com' => 1, 'nobody@example.com' => 0] as $email => $emailsSent) {
            $client->request('GET', '/mot-de-passe/oublie');
            $client->request('POST', '/mot-de-passe/oublie', ['_csrf_token' => self::props($client)['csrfToken'], 'email' => $email]);
            self::assertResponseRedirects('/mot-de-passe/oublie');
            self::assertEmailCount($emailsSent);
            $client->followRedirect();
            self::assertTrue(self::props($client)['sent']);
        }
    }

    public function testCrossSiteApiWritesAreRejected(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print'], ['HTTP_SEC_FETCH_SITE' => 'cross-site']);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print'], ['HTTP_ORIGIN' => 'https://evil.example']);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print'], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        self::assertResponseStatusCodeSame(201);
    }

    private function signIn(KernelBrowser $client, string $email, string $password): void
    {
        $client->request('GET', '/connexion');
        $client->request('POST', '/connexion', ['_csrf_token' => self::props($client)['csrfToken'], 'email' => $email, 'password' => $password]);
    }

    /** @return array<string, mixed> */
    private static function props(KernelBrowser $client): array
    {
        $json = $client->getCrawler()->filter('[data-symfony--ux-vue--vue-props-value]')->attr('data-symfony--ux-vue--vue-props-value');

        return json_decode((string) $json, true, flags: \JSON_THROW_ON_ERROR);
    }

    private function linkFromLastEmail(): string
    {
        $messages = self::getMailerMessages();
        $email = end($messages);
        self::assertInstanceOf(Email::class, $email);
        preg_match('#https?://[^/]+(/mot-de-passe/definir/[0-9a-f]+)#', (string) $email->getHtmlBody(), $matches);

        return $matches[1];
    }
}
