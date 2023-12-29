<?php

namespace App\Voter;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AccessVoter extends Voter
{
    public const ACCESS_ATTR = 'access';

    public function __construct(private AppContext $appContext)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS_ATTR === $attribute && $subject instanceof ConnectedDevice;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::ACCESS_ATTR !== $attribute) {
            return false;
        }

        if (!$subject instanceof ConnectedDevice) {
            throw new \LogicException('Needing a ConnectedDevice object to vote');
        }

        if (!$subject->isActive()) {
            return false;
        }

        // @todo nick How to detect app dashboard?
        $app = $this->appContext->getApp();
        if (null === $app) {
            return true;
        }

        return $subject->hasAccessToApp($this->appContext->getApp());
    }
}
