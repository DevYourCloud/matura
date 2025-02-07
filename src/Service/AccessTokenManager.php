<?php

namespace App\Service;

use App\Entity\AccessToken;

class AccessTokenManager
{
    public function __construct(
        private EncryptionService $encryptionService,
        private string $accessTokenParameterName,
    ) {
    }

    public function getTokenUrl(AccessToken $token): string
    {
        return 'https://'.$token->getServer()->getHost().'?'.\http_build_query([
            $this->accessTokenParameterName => $token->getAccessToken(),
        ]);
    }

    public function generateAccessTokenData(AccessToken $accessToken): void
    {
        if (null !== $accessToken->getId()) {
            throw new \LogicException('Access token already generated');
        }

        $accessToken->setAccessToken($this->encryptionService->createAccessToken());
        if (null !== $accessToken->getValidityPeriod()) {
            $date = new \DateTime();
            $date->setTime(0, 0, 0);
            $date->add(new \DateInterval('P'.($accessToken->getValidityPeriod() + 1).'D'));
            $accessToken->setValidity($date);
        } else {
            $accessToken->setValidity(null);
        }
    }

    public function isValidAccessToken(AccessToken $accessToken): bool
    {
        $now = new \DateTime();

        if (!$accessToken->isActive() || !$accessToken->getServer()->isActive()) {
            return false;
        }

        if (null === $accessToken->getValidity()) {
            return true;
        }

        return $now <= $accessToken->getValidity();
    }
}
