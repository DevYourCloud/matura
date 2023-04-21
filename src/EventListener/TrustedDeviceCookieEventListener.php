<?php

namespace App\EventListener;

use App\Context\AppContext;
use App\Security\ConnectedDeviceAuthenticator;
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
        private ConnectedDeviceAuthenticator $connectedDeviceAuthenticator,
        private string $trustedDeviceCookieName,
    ) {
    }

    public function __invoke(ResponseEvent $responseEvent)
    {
        if (!$this->appContext->hasValidForwardedAuthRequest()) {
            return;
        }

        if (!$this->appContext->createTrustedCookie()) {
            return;
        }

        $response = $responseEvent->getResponse();

        $device = $this->connectedDeviceAuthenticator->getNewDevice(
            $this->appContext->getServer(),
            $this->appContext->getForwardedRequest()
        );

        $token = $this->encryptionService->createTrustedDeviceToken($device);

        // Set the cookie
        $cookie = new Cookie(
            $this->trustedDeviceCookieName,
            \urlencode($token),
            $this->encryptionService->getTokenExpirationDate(),
            '/',
            $this->appContext->getServer()->getHost()->getDomain(),
            true,
        );

        $response->headers->setCookie($cookie);
    }
}
