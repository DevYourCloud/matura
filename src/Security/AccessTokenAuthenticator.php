<?php

namespace App\Security;

use App\Context\AppContext;
use App\Model\ForwardedRequest;
use App\Repository\AccessTokenRepository;
use App\Service\AccessTokenManager;
use App\Service\EncryptionService;
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

class AccessTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $accessTokenParameterName,
        private AccessTokenRepository $accessTokenRepository,
        private AccessTokenManager $accessTokenManager,
        private LoggerInterface $logger,
        private AppContext $appContext,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($this->appContext->isAccessGranted()) {
            return false;
        }

        $accessTokenParams = null !== ForwardedRequest::searchAccessTokenInUri(
            $request->headers->get(ForwardedRequest::HEADER_URI),
            $this->accessTokenParameterName
        );

        $accessTokenCookie = $request->cookies->has($this->accessTokenParameterName);

        return $accessTokenParams || $accessTokenCookie;
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->debug(json_encode($request->headers->all()));
        $this->logger->debug(json_encode($request->query->all()));
        $this->logger->debug(json_encode($request->request->all()));
        $this->logger->debug(json_encode($request->getContent()));

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));

        $accessToken = $this->appContext->getForwardedRequest()->getAccessTokenFromRequest($this->accessTokenParameterName);

        if (null === $accessToken || '' === $accessToken || EncryptionService::ACCESS_TOKEN_LENGTH !== strlen($accessToken)) {
            throw new CustomUserMessageAuthenticationException(sprintf('[ACCESS TOKEN] Invalid format : "%s"', $request->headers->get(ForwardedRequest::HEADER_URI)));
        }

        $accessTokenEntity = $this->accessTokenRepository->getByAccessToken($accessToken);

        if (null === $accessTokenEntity) {
            throw new CustomUserMessageAuthenticationException(sprintf('[ACCESS TOKEN] No access token found : "%s"', $accessToken));
        }

        if (!$this->accessTokenManager->isValidAccessToken($accessTokenEntity)) {
            throw new CustomUserMessageAuthenticationException(sprintf('[ACCESS TOKEN] Expired token or not active token "%s"', $accessToken));
        }

        $this->appContext->setAccessToken($accessTokenEntity);
        $this->appContext->setAccessGranted(true);

        if (!$request->cookies->has($this->accessTokenParameterName)) {
            $this->appContext->setCreateAccessToken(true);
        }

        return new SelfValidatingPassport(new UserBadge($accessTokenEntity->getServer()->getUser()->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
