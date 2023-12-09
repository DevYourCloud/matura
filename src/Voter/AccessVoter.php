<?php

namespace App\Voter;

use App\Context\AppContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AccessVoter extends Voter
{
    public const ACCESS_ATTR = 'access';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS_ATTR === $attribute && $subject instanceof AppContext;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::ACCESS_ATTR !== $attribute) {
            return false;
        }

        if (!$subject instanceof AppContext) {
            throw new \LogicException('Needing a AppContext object to vote');
        }

        $connectedDevice = $subject->getConnectedDevice();

        // @todo nick How to detect app dashboard?
        $app = $subject->getApp();
        if (null === $app) {
            return true;
        }

        return $connectedDevice->hasAccessToApp($subject->getApp());
    }
}
