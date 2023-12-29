<?php

namespace App\EventListener;

use App\Context\AppContext;
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
        private LoggerInterface $logger,
        private string $trustedDeviceCookieName,
    ) {
    }

    public function __invoke(ResponseEvent $responseEvent)
    {
        if (!$this->appContext->createTrustedCookie()) {
            return;
        }

        $server = $this->appContext->getServer();

        if (!$server->isPairing()) {
            $this->logger->info(sprintf('Paring not active on server %s', $server->getName()));

            return;
        }

        $response = $responseEvent->getResponse();

        $token = $this->encryptionService->createTrustedDeviceToken($this->appContext->getConnectedDevice());

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
