<?php

namespace App\EventListener;

use App\Context\AppContext;
use App\Factory\ConnectedDeviceFactory;
use App\Service\EncryptionService;
use Psr\Log\LoggerInterface;
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
