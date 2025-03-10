<?php

namespace App\Model;

use App\Service\EncryptionService;
use Symfony\Component\HttpFoundation\Request;

class ForwardedRequest
{
    public const HEADER_METHOD = 'x-forwarded-method';
    public const HEADER_PROTO = 'x-forwarded-proto';
    public const HEADER_HOST = 'x-forwarded-host';
    public const HEADER_URI = 'x-forwarded-uri';
    public const HEADER_FOR = 'x-forwarded-for';
    public const HEADER_USER_AGENT = 'user-agent';

    public function __construct(
        private Request $request,
    ) {
    }

    public function __toString(): string
    {
        return print_r($this->request->headers->all(), true);
    }

    public function getForwardedUri(): ?string
    {
        return $this->request->headers->get(self::HEADER_URI) ?? null;
    }

    public function getForwardedHost(): ?string
    {
        return $this->request->headers->get(self::HEADER_HOST) ?? null;
    }

    public function getForwardedProto(): ?string
    {
        return $this->request->headers->get(self::HEADER_PROTO) ?? null;
    }

    public function getForwardedIp(): ?string
    {
        return $this->request->headers->get(self::HEADER_FOR) ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->request->headers->get(self::HEADER_USER_AGENT) ?? null;
    }

    public function isValid(): bool
    {
        return $this->getForwardedHost() && $this->getForwardedIp() && $this->getForwardedProto();
    }

    public function getTrustedDeviceCookie(string $name): ?string
    {
        return $this->request->cookies->get($name, null);
    }

    public function getAccessTokenFromRequest(string $accessTokenParameterName): ?string
    {
        if ($this->request->cookies->has($accessTokenParameterName)) {
            return $this->request->cookies->get($accessTokenParameterName);
        } else {
            return self::searchAccessTokenInUri($this->request->headers->get(ForwardedRequest::HEADER_URI), $accessTokenParameterName);
        }
    }

    public static function searchAccessTokenInUri(string $subject, string $accessTokenParameterName): ?string
    {
        $match = [];
        preg_match(
            sprintf('/%s=([\w-]{%d})/', $accessTokenParameterName, EncryptionService::ACCESS_TOKEN_LENGTH),
            $subject,
            $match
        );

        return $match[1] ?? null;
    }
}
