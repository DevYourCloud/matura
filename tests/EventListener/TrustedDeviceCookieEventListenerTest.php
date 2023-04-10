<?php

namespace App\Tests\EventListener;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use App\EventListener\TrustedDeviceCookieEventListener;
use App\Model\ForwardedRequest;
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

    public function setUp(): void
    {
        $this->tokenLifetime = $this->getContainer()->getParameter('token_lifetime');

        // @var EncryptionService $encryptionService
        $this->encryptionService = $this->getContainer()->get(EncryptionService::class);
    }

    /** @group nick */
    public function testCookieAddedOnGrantedAccess(): void
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
        $hash = 'HASH_TEST';
        $appContext = $this->createAppContext($request, $hash, true);

        $responseEvent = new ResponseEvent(self::$kernel, $request, 0, new Response());
        $eventListener = new TrustedDeviceCookieEventListener($appContext, $this->encryptionService, '_trusted_cookie');

        $eventListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();
        $cookie = $response->headers->getCookies()[0];

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertEquals($host, $cookie->getDomain());
        self::assertTrue($cookie->isSecure());

        $cookieTime = new \DateTime();
        $cookieTime->setTimestamp($cookie->getExpiresTime());

        $expiryTime = new \DateTime();
        $expiryTime->add(new \DateInterval('P'.$this->tokenLifetime.'D'));

        self::assertEquals($expiryTime->format('Y-m-d'), $cookieTime->format('Y-m-d'));

        $token = $this->encryptionService->decodeTrustedDeviceToken($cookie->getValue());

        self::assertEquals('HASH_TEST', $token);
    }

    public function testNoCookieOnNotGrantedAccess(): void
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
        $hash = 'HASH_TEST_2';
        $appContext = $this->createAppContext($request, $hash, false);

        $responseEvent = new ResponseEvent(self::$kernel, $request, 0, new Response());
        $eventListener = new TrustedDeviceCookieEventListener($appContext, $this->encryptionService, '_trusted_cookie');

        $eventListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();
        self::assertCount(0, $response->headers->getCookies());
    }

    private function createAppContext(Request $request, string $deviceHash, bool $access): AppContext
    {
        $connectedDeviceSample = (new ConnectedDevice())
            ->setHash($deviceHash)
        ;

        /** @var AppContext $appContext */
        $appContext = $this->getContainer()->get(AppContext::class);
        $appContext->initializeFromRequest(new ForwardedRequest($request));
        $appContext->setConnectedDevice($connectedDeviceSample);
        $appContext->setAccessGranted($access);

        return $appContext;
    }
}
