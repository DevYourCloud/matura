<?php

namespace App\Security;

use App\Context\AppContext;
use App\Exception\DecodingTokenFailed;
use App\Model\ForwardedRequest;
use App\Service\ConnectedDeviceManager;
use App\Voter\AccessVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TrustedDeviceAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $trustedDeviceCookieName,
        private ConnectedDeviceManager $connectedDeviceManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AppContext $appContext,
        private LoggerInterface $logger
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(ForwardedRequest::HEADER_FOR);
    }

    public function authenticate(Request $request): Passport
    {
        $forwardedRequest = new ForwardedRequest($request);

        $this->logger->debug(json_encode($request->headers->all()));
        $this->logger->debug(json_encode($request->query->all()));
        $this->logger->debug(json_encode($request->request->all()));
        $this->logger->debug(json_encode($request->getContent()));

        try {
            $this->appContext->initializeFromRequest($forwardedRequest);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException(sprintf('[COOKIE AUTH] Initialization failed : %s - %s', $e->getMessage(), $forwardedRequest->getForwardedHost()));
        }

        $server = $this->appContext->getServer();

        if (!$request->cookies->has($this->trustedDeviceCookieName)) {
            $this->appContext->setCreateTrustedCookie(true);

            throw new CustomUserMessageAuthenticationException('[COOKIE AUTH] No trusted cookie, setting up for creation');
        }

        $token = \urldecode($request->cookies->get($this->trustedDeviceCookieName));
        $connectedDevice = null;

        try {
            $connectedDevice = $this->connectedDeviceManager->decodeAndFindConnectedDevice($token);
        } catch (DecodingTokenFailed $e) {
            $this->appContext->setCreateTrustedCookie(true);

            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }

        if (null === $connectedDevice) {
            $this->appContext->setCreateTrustedCookie(true);

            throw new CustomUserMessageAuthenticationException('[COOKIE AUTH] No device found');
        }

        $server = $connectedDevice->getServer();

        $this->appContext->setConnectedDevice($connectedDevice);

        if ($this->authorizationChecker->isGranted(AccessVoter::ACCESS_ATTR, $connectedDevice)) {
            return new SelfValidatingPassport(new UserBadge($server->getUser()->getUserIdentifier()));
        }

        throw new CustomUserMessageAuthenticationException('[COOKIE AUTH] Server or device not active');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->appContext->setAccessGranted(true);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
