<?php

namespace App\Tests\EventListener;

use App\EventListener\OnKernelResponseRefreshCookie;
use App\Service\EncryptionService;
use App\Tests\Builder\ServiceBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class OnKernelResponseRefreshCookieTest extends TestCase
{
    private EncryptionService $encryptionService;
    private int $expirationDelay = 30; // days
    private string $cookieName = 'trusted_cookie';

    public function setup(): void
    {
        $this->encryptionService = ServiceBuilder::getEncryptionService($this->expirationDelay);
    }

    public function testRefreshCookie(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $cookieExpirationDate = new \DateTime('now');
        $cookieExpirationDate->add(new \DateInterval('P10D'));

        $listener = new OnKernelResponseRefreshCookie(
            $this->encryptionService,
            $this->cookieName,
            $this->expirationDelay
        );

        $existingCookie = new Cookie(
            $this->cookieName,
            'THIS_IS_A_TEST',
            $cookieExpirationDate,
            '/',
            'example.test',
            true
        );

        $request = new Request();
        $request->cookies->add([$this->cookieName => $existingCookie]);

        $response = new Response();

        $eventMock = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->__invoke($eventMock);

        $responseCookie = Cookie::fromString($response->headers->get('set-cookie'));

        self::assertNotNull($responseCookie);
        self::assertEquals($this->cookieName, $responseCookie->getName());
        self::assertEquals($existingCookie->getValue(), $responseCookie->getValue());
        self::assertEquals($existingCookie->getName(), $responseCookie->getName());
        self::assertEquals($existingCookie->getDomain(), $responseCookie->getDomain());
        self::assertEquals($existingCookie->getPath(), $responseCookie->getPath());

        $expiration = new \DateTime();
        $expiration->add(new \DateInterval('P'.$responseCookie->getExpiresTime().'D'));
        self::assertEquals(
            $this->encryptionService->getTokenExpirationDate(new \DateTime('now'))->format('Y-m-d'),
            $expiration->format('Y-m-d')
        );
    }

    public function testCookieNotRefreshed(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $cookieExpirationDate = new \DateTime('now');
        $cookieExpirationDate->add(new \DateInterval('P'.$this->expirationDelay.'D'));

        $listener = new OnKernelResponseRefreshCookie(
            $this->encryptionService,
            $this->cookieName,
            $this->expirationDelay
        );

        $existingCookie = new Cookie(
            $this->cookieName,
            'THIS_IS_A_TEST',
            $cookieExpirationDate,
            '/',
            'example.test',
            true
        );

        $request = new Request();
        $request->cookies->add([$this->cookieName => $existingCookie]);

        $response = new Response();

        $eventMock = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->__invoke($eventMock);

        self::assertNull($response->headers->get('set-cookie'));
    }
}
