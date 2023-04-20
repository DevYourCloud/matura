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
        $response = $responseEvent->getResponse();
        $response->headers->setCookie(new Cookie('test', 'test'));

        if (!$this->appContext->hasValidForwardedAuthRequest()) {
            return;
        }

        if (!$this->appContext->createTrustedCookie()) {
            return;
        }

        // $trustedCookie = $this->appContext->getForwardedRequest()->getTrustedDeviceCookie($this->trustedDeviceCookieName);

        // if (null !== $trustedCookie) {
        //     return;
        // }

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
            null,
            true,
        );

        $response->headers->setCookie($cookie);

        // $response->headers->add(['X-Auth-ID' => $token]);
    }
}
