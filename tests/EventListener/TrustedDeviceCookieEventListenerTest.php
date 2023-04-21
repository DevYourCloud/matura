<?php

namespace App\Tests\EventListener;

use App\Context\AppContext;
use App\DataFixtures\MainFixtures;
use App\EventListener\TrustedDeviceCookieEventListener;
use App\Model\ForwardedRequest;
use App\Security\ConnectedDeviceAuthenticator;
use App\Service\EncryptionService;
use App\Tests\FixtureAwareTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 *
 * @coversNothing
 */
class TrustedDeviceCookieEventListenerTest extends FixtureAwareTestCase
{
    private int $tokenLifetime = 0;

    private EncryptionService $encryptionService;
    private ConnectedDeviceAuthenticator $trustedDeviceAuthenticator;

    private string $trustedCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $this->addFixture(new MainFixtures());
        $this->executeFixtures();

        $this->tokenLifetime = $this->getContainer()->getParameter('token_lifetime');

        $this->encryptionService = $this->getContainer()->get(EncryptionService::class);
        $this->trustedDeviceAuthenticator = $this->getContainer()->get(ConnectedDeviceAuthenticator::class);
        $this->trustedCookieName = static::getContainer()->getParameter('trusted_device_cookie_name');
    }

    /** @group nick */
    public function testCookieAddedOnRequested(): void
    {
        $host = 'authorized.devyour.cloud';

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);
        $appContext = $this->createAppContext($request, true);

        $responseEvent = new ResponseEvent(self::$kernel, $request, 0, new Response());
        $eventListener = new TrustedDeviceCookieEventListener(
            $appContext,
            $this->encryptionService,
            $this->trustedDeviceAuthenticator,
            $this->trustedCookieName
        );

        $eventListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();

        self::assertCount(1, $response->headers->getCookies());
        $cookie = $response->headers->getCookies()[0];

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertEquals($host, $cookie->getDomain());
        self::assertTrue($cookie->isSecure());

        $cookieTime = new \DateTime();
        $cookieTime->setTimestamp($cookie->getExpiresTime());

        $expiryTime = new \DateTime();
        $expiryTime->add(new \DateInterval('P'.$this->tokenLifetime.'D'));

        self::assertEquals($expiryTime->format('Y-m-d'), $cookieTime->format('Y-m-d'));

        $token = $this->encryptionService->decodeTrustedDeviceToken(\urldecode($cookie->getValue()));

        self::assertNotNull($token);
    }

    public function testNoCookieOnUnauthorizedAccess(): void
    {
        $host = 'unauthorized.devyour.cloud';

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);
        $appContext = $this->createAppContext($request, false);

        $responseEvent = new ResponseEvent(self::$kernel, $request, 0, new Response());
        $eventListener = new TrustedDeviceCookieEventListener(
            $appContext,
            $this->encryptionService,
            $this->trustedDeviceAuthenticator,
            $this->trustedCookieName
        );

        $eventListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();
        self::assertCount(0, $response->headers->getCookies());
    }

    private function createAppContext(Request $request, bool $createCookie): AppContext
    {
        /** @var AppContext $appContext */
        $appContext = $this->getContainer()->get(AppContext::class);
        $appContext->initializeFromRequest(new ForwardedRequest($request));
        $appContext->setCreateTrustedCookie($createCookie);

        return $appContext;
    }
}
