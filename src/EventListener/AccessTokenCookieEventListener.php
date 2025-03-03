<?php

namespace App\EventListener;

use App\Context\AppContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class AccessTokenCookieEventListener
{
    public function __construct(
        private AppContext $appContext,
        private string $accessTokenParameterName,
        private string $cookieLifetime,
    ) {
    }

    public function __invoke(ResponseEvent $responseEvent): void
    {
        if (!$this->appContext->createAccessToken()) {
            return;
        }

        $request = $responseEvent->getRequest();

        $response = new RedirectResponse('https://'.$request->getHost().$request->getBasePath());
        $accessToken = $this->appContext->getAccessToken();

        $expirationDate = new \DateTime('now');
        $expirationDate->add(new \DateInterval('P'.$this->cookieLifetime.'D'));

        // Set the cookie
        $cookie = new Cookie(
            $this->accessTokenParameterName,
            \urlencode($accessToken->getAccessToken()),
            $expirationDate,
            '/',
            $this->appContext->getServer()->getHost()->getDomain(),
            true,
        );

        $response->headers->setCookie($cookie);

        $responseEvent->setResponse($response);
    }
}
