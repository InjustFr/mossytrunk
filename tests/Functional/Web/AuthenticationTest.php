<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Domain\Identity\UserRepository;
use App\Infrastructure\Security\SecurityUser;
use App\Tests\Support\FakeAccounts;
use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthenticationTest extends WebTestCase
{
    use SignsInClient;

    public function testAnonymousPagesRedirectToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/products');

        self::assertResponseRedirects('/login');
    }

    public function testAnonymousApiCallsAreUnauthorized(): void
    {
        $client = self::createClient();

        $client->jsonRequest('GET', '/api/products');

        self::assertResponseStatusCodeSame(401);
    }

    public function testSignedInUsersSkipTheLogin(): void
    {
        $client = self::signedInClient();

        $client->request('GET', '/login');

        self::assertResponseRedirects('/dashboard');
    }

    public function testSignedOutVisitorsSignInWithTheirMossyleafAccount(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        $query = $this->authorizeQuery($client);
        self::assertSame(['code', 'mossytrunk', 'http://localhost/login/check', 'openid email profile', 'S256'], [$query['response_type'], $query['client_id'], $query['redirect_uri'], $query['scope'], $query['code_challenge_method']]);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{22,}$/', $query['state']);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $query['code_challenge']);
    }

    public function testANewAccountSignsInAndLandsWhereItWasGoing(): void
    {
        $client = self::createClient();
        $client->request('GET', '/products');
        $client->request('GET', '/login');
        $state = $this->authorizeQuery($client)['state'];

        $client->request('GET', '/login/check', ['state' => $state, 'code' => FakeAccounts::code(['sub' => 'account-1', 'email' => 'fern@example.com'])]);

        self::assertResponseRedirects('/products');
        $client->jsonRequest('GET', '/api/products');
        self::assertResponseIsSuccessful();
        self::assertSame('Atelier Mousse', self::getContainer()->get(UserRepository::class)->findByAccountId('account-1')?->workspace()->name());
        self::assertNotNull($client->getCookieJar()->get('REMEMBERME'));
    }

    public function testAnExistingUserSignsInWithTheirEmail(): void
    {
        $client = self::createClient();
        $user = self::createMemberFromBeforeAccounts('Atelier', 'louis@example.com');
        $client->request('GET', '/login');

        $client->request('GET', '/login/check', ['state' => $this->authorizeQuery($client)['state'], 'code' => FakeAccounts::code(['sub' => 'account-1', 'email' => 'louis@example.com'])]);

        self::assertResponseRedirects('/dashboard');
        self::assertSame((string) $user->id(), (string) self::getContainer()->get(UserRepository::class)->findByAccountId('account-1')?->id());
    }

    public function testAnUnknownStateIsRefused(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->request('GET', '/login/check', ['state' => 'forged', 'code' => FakeAccounts::code(['sub' => 'account-1', 'email' => 'fern@example.com'])]);
        self::assertResponseRedirects('/login');
        $client->followRedirect();

        self::assertSame('This sign-in took too long or was opened in another tab, please try again.', Json::string(self::props($client), 'error'));
        $client->jsonRequest('GET', '/api/products');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAStateIsUsedOnlyOnce(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');
        $state = $this->authorizeQuery($client)['state'];
        $client->request('GET', '/login/check', ['state' => $state, 'code' => 'not-a-code']);
        $client->followRedirect();
        self::assertSame('Unable to sign in, please try again.', Json::string(self::props($client), 'error'));

        $client->request('GET', '/login/check', ['state' => $state, 'code' => FakeAccounts::code(['sub' => 'account-1', 'email' => 'fern@example.com'])]);
        $client->followRedirect();

        self::assertSame('This sign-in took too long or was opened in another tab, please try again.', Json::string(self::props($client), 'error'));
    }

    public function testAnAccountWithoutAccessSeesWhy(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        $client->request('GET', '/login/check', ['state' => $this->authorizeQuery($client)['state'], 'error' => 'access_denied']);
        $client->followRedirect();

        self::assertSame('Your mossyleaf account does not have access to MossyTrunk yet.', Json::string(self::props($client), 'error'));
    }

    public function testAnEmailTakenByAnotherAccountIsExplained(): void
    {
        $client = self::createClient();
        self::createMember('Atelier', 'louis@example.com');
        $client->request('GET', '/login');

        $client->request('GET', '/login/check', ['state' => $this->authorizeQuery($client)['state'], 'code' => FakeAccounts::code(['sub' => 'account-2', 'email' => 'louis@example.com'])]);
        $client->followRedirect();

        self::assertSame('A user already exists with the address “louis@example.com”.', Json::string(self::props($client), 'error'));
    }

    public function testSigningOutAlsoSignsOutOfTheMossyleafAccount(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');
        $code = FakeAccounts::code(['sub' => 'account-1', 'email' => 'fern@example.com']);
        $client->request('GET', '/login/check', ['state' => $this->authorizeQuery($client)['state'], 'code' => $code]);

        $client->request('POST', '/logout', ['_csrf_token' => $this->logoutToken($client)]);

        self::assertResponseRedirects('https://accounts.test/end-session?client_id=mossytrunk&id_token_hint=id.'.$code.'&post_logout_redirect_uri=http%3A%2F%2Flocalhost%2F');
        $client->jsonRequest('GET', '/api/products');
        self::assertResponseStatusCodeSame(401);
    }

    public function testSigningOutOfASessionWithoutIdTokenOnlyEndsTheMossyleafSession(): void
    {
        $client = self::createClient();
        $client->loginUser(SecurityUser::fromUser(self::createMember()));

        $client->request('POST', '/logout', ['_csrf_token' => $this->logoutToken($client)]);

        self::assertResponseRedirects('https://accounts.test/end-session?client_id=mossytrunk');
        $client->jsonRequest('GET', '/api/products');
        self::assertResponseStatusCodeSame(401);
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

    private function logoutToken(KernelBrowser $client): string
    {
        $client->request('GET', '/settings');
        $session = Json::decode((string) $client->getCrawler()->filter('#app-session')->text());
        self::assertSame('https://accounts.test', Json::string($session, 'accountsUrl'));

        return Json::string($session, 'logoutToken');
    }

    /**
     * @return array<string, string>
     */
    private function authorizeQuery(KernelBrowser $client): array
    {
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('https://accounts.test/authorize?', $location);
        parse_str((string) parse_url($location, \PHP_URL_QUERY), $query);

        $strings = [];
        foreach ($query as $name => $value) {
            $strings[(string) $name] = \is_string($value) ? $value : '';
        }

        return $strings;
    }

    /**
     * @return array<mixed>
     */
    private static function props(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getCrawler()->filter('[data-symfony--ux-vue--vue-props-value]')->attr('data-symfony--ux-vue--vue-props-value'));
    }
}
