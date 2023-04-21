<?php

namespace App\Security;

use App\Context\AppContext;
use App\Entity\Application;
use App\Entity\Server;
use App\Model\ForwardedRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ForwardedAuthAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $trustedDeviceCookieName,
        private ConnectedDeviceAuthenticator $connectedDeviceAuthenticator,
        private LoggerInterface $logger,
        private AppContext $appContext
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return false;

        return $request->headers->has(ForwardedRequest::HEADER_FOR) && !$request->cookies->has($this->trustedDeviceCookieName);
    }

    public function authenticate(Request $request): Passport
    {
        $forwardedRequest = new ForwardedRequest($request);

        $this->appContext->initializeFromRequest($forwardedRequest);

        // $this->logger->info(sprintf('[REQUEST AUTH] Checking access for request "%s"', $forwardedRequest->getForwardedHost()));

        // $server = $this->appContext->getServer();

        // $this->appContext->setConnectedDevice(
        //     $this->connectedDeviceAuthenticator->getDevice($server, $forwardedRequest)
        // );

        /*if ($this->isRequestAuthorized($forwardedRequest, $server) && $this->isAppActive($forwardedRequest)) {
            $this->appContext
            $this->logger->info(sprintf('[REQUEST AUTH] Access granted for "%s"', $forwardedRequest->getForwardedHost()));

            return new SelfValidatingPassport(new UserBadge($server->getUser()->getUserIdentifier()));
        }*/
        $this->appContext->setCreateTrustedCookie(true);

        throw new CustomUserMessageAuthenticationException(
            sprintf('[REQUEST AUTH] User unauthorized to access to the endpoint')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->appContext->setAccessGranted(true);

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
    }

    private function isRequestAuthorized(ForwardedRequest $forwardedRequest, Server $server): bool
    {
        $connectedDevice = $this->connectedDeviceAuthenticator->getDevice($server, $forwardedRequest);

        if ($connectedDevice && $connectedDevice->isActive()) {
            $this->appContext->setConnectedDevice($connectedDevice);
            $this->logger->info(
                sprintf(
                    '[DEVICE AUTH] Device authentication successful %s - %s',
                    $forwardedRequest->getForwardedIp(),
                    $forwardedRequest->getUserAgent()
                )
            );

            return true;
        }

        $this->logger->info(sprintf(
            '[REQUEST AUTH] authentication request failed for: %s with IP: %s',
            $forwardedRequest->getForwardedHost(),
            $forwardedRequest->getForwardedIp()
        ));

        return false;
    }

    private function isAppActive(ForwardedRequest $forwardedRequest): bool
    {
        $app = $this->appContext->getApp();

        if ($app instanceof Application && !$app->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                sprintf('Application inactive : %s', $forwardedRequest->getForwardedHost())
            );
        }

        return true;
    }
}
