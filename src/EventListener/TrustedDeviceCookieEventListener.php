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
        private string $trustedDeviceCookieName
    ) {
    }

    public function __invoke(ResponseEvent $responseEvent)
    {
        if (!$this->appContext->hasValidForwardedAuthRequest()) {
            return;
        }
        if (!$this->appContext->isAccessGranted()) {
            return;
        }

        $trustedCookie = $this->appContext->getForwardedRequest()->getTrustedDeviceCookie($this->trustedDeviceCookieName);

        if (null !== $trustedCookie) {
            return;
        }

        $response = $responseEvent->getResponse();

        // Set the cookie
        $cookie = new Cookie(
            $this->trustedDeviceCookieName,
            $this->encryptionService->createTrustedDeviceToken($this->appContext->getConnectedDevice()),
            $this->encryptionService->getTokenExpirationDate(),
            '/',
            $this->appContext->getForwardedRequest()->getForwardedHost(),
            true
        );

        $response = $responseEvent->getResponse();
        $response->headers->setCookie($cookie);
    }
}
