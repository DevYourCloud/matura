<?php

namespace App\EventListener;

use App\Context\AppContext;
use App\Service\EncryptionService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class TrustedDeviceCookieEventListener
{
    public function __construct(
        private AppContext $appContext,
        private EncryptionService $encryptionService,
        private string $trustedDeviceCookieName,
        private string $cookieLifetime,
    ) {
    }

    public function __invoke(ResponseEvent $responseEvent): void
    {
        if (!$this->appContext->createTrustedCookie() || null === $this->appContext->getConnectedDevice()) {
            return;
        }

        $response = $responseEvent->getResponse();
        $connectedDevice = $this->appContext->getConnectedDevice();

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        $expirationDate = new \DateTime('now');
        $expirationDate->add(new \DateInterval('P'.$this->cookieLifetime.'D'));

        // Set the cookie
        $cookie = new Cookie(
            $this->trustedDeviceCookieName,
            \urlencode($token),
            $expirationDate,
            '/',
            $this->appContext->getServer()->getHost()->getDomain(),
            true,
        );

        $response->headers->setCookie($cookie);
    }
}
