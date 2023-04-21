<?php

namespace App\Security;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use App\Model\ForwardedRequest;
use App\Repository\ConnectedDeviceRepository;
use App\Service\EncryptionService;
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

class TrustedDeviceAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $trustedDeviceCookieName,
        private EncryptionService $encryptionService,
        private ConnectedDeviceRepository $connectedDeviceRepository,
        private AppContext $appContext
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(ForwardedRequest::HEADER_FOR);
    }

    public function authenticate(Request $request): Passport
    {
        $forwardedRequest = new ForwardedRequest($request);

        try {
            $this->appContext->initializeFromRequest($forwardedRequest);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException(sprintf(
                '[COOKIE AUTH] Initialization failed : %s - %s',
                $e->getMessage(),
                $forwardedRequest->getForwardedHost()
            ));
        }

        if (!$request->cookies->has($this->trustedDeviceCookieName)) {
            if ($this->appContext->getServer()->isPairing()) {
                $this->appContext->setCreateTrustedCookie(true);

                throw new CustomUserMessageAuthenticationException(
                    sprintf('[COOKIE AUTH] No trusted cookie, setting up for creation')
                );
            }

            throw new CustomUserMessageAuthenticationException(
                sprintf('[COOKIE AUTH] Server not in pairing mode, no trusted device creation')
            );
        }

        $token = \urldecode($request->cookies->get($this->trustedDeviceCookieName));

        try {
            $decoded = $this->encryptionService->decodeTrustedDeviceToken($token);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException(sprintf('[COOKIE AUTH] Encryption service : %s - %s', $e->getMessage(), $token));
        }

        /** @var ?ConnectedDevice $connectedDevice */
        $connectedDevice = $this->connectedDeviceRepository->findOneBy(['hash' => $decoded]);

        if (null === $connectedDevice) {
            throw new CustomUserMessageAuthenticationException(sprintf('[COOKIE AUTH] No device found with hash %s', $decoded));
        }

        $server = $connectedDevice->getServer();

        $this->appContext->setConnectedDevice($connectedDevice);

        if ($connectedDevice->isActive() && $server->isActive()) {
            return new SelfValidatingPassport(new UserBadge($server->getUser()->getUserIdentifier()));
        }

        throw new CustomUserMessageAuthenticationException(sprintf('[COOKIE AUTH] Server or device not active for hash %s', $decoded));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->appContext->setAccessGranted(true);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
    }
}
