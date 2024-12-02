<?php

namespace App\EventListener;

use App\Service\EncryptionService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener()]
class OnKernelResponseRefreshCookie
{
    public function __construct(
        private EncryptionService $encryptionService,
        private string $trustedDeviceCookieName,
        private int $cookieLifetime,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $cookie = $request->cookies->get($this->trustedDeviceCookieName, null);

        if (null === $cookie) {
            return;
        }

        $cookie = Cookie::fromString($cookie);

        $expirationLifetime = ((int) $this->cookieLifetime / 2);

        $expirationDate = new \DateTime();
        $expirationDate->setTimestamp($cookie->getExpiresTime());

        $now = new \DateTime('now');

        $expirationDelay = $expirationDate->diff($now);

        if ($expirationDelay instanceof \DateInterval && $expirationDelay->days < $expirationLifetime) {
            $response = $event->getResponse();

            // Set the cookie
            $refreshedCookie = new Cookie(
                $this->trustedDeviceCookieName,
                $cookie->getValue(),
                $this->cookieLifetime,
                '/',
                $cookie->getDomain(),
                true,
            );

            $response->headers->setCookie($refreshedCookie);
        }
    }
}
