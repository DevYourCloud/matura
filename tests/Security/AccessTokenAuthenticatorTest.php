<?php

namespace App\Tests\Security;

use App\Repository\AccessTokenRepository;
use App\Security\AccessTokenAuthenticator;
use App\Service\AccessTokenManager;
use App\Tests\Builder\AccessTokenEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Builder\UserEntityBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AccessTokenAuthenticatorTest extends TestCase
{
    private AccessTokenAuthenticator $accessTokenAuthenticator;
    private AccessTokenRepository|MockObject $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(AccessTokenRepository::class);

        $this->accessTokenAuthenticator = new AccessTokenAuthenticator(
            'access_token',
            $this->repository,
            new AccessTokenManager(ServiceBuilder::getEncryptionService(), 'access_token')
        );
    }

    public function testAuthenticate(): void
    {
        $user = UserEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()
            ->withHost(HostEntityBuilder::create()->withDomain('test.example.com')->build())
            ->withUser($user)
            ->build()
        ;

        $token = 'LZKvPzGgwvoGEiSrcjDoZH6PFr7K1ntj';
        $accessToken = AccessTokenEntityBuilder::create()
            ->withToken($token)
            ->withServer($server)
            ->build()
        ;

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'test.example.com',
            'X-Forwarded-Uri' => '/test?access_token='.$token,
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->repository->expects($this->any())->method('getByAccessToken')->willReturn($accessToken);

        $result = $this->accessTokenAuthenticator->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $result);
    }

    public function testAuthenticateFailBecauseTokenIsInvalid(): void
    {
        $token = 'ABC';

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'test.example.com',
            'X-Forwarded-Uri' => '/test?access_token='.$token,
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(sprintf('[ACCESS TOKEN] Invalid format : "/test?access_token=%s"', $token));

        $this->accessTokenAuthenticator->authenticate($request);
    }

    public function testAuthenticateFailBecauseTokenDoesNotExists(): void
    {
        $token = 'LZKvPzGgwvoGEiSrcjDoZH6PFr7K1ntj';

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'test.example.com',
            'X-Forwarded-Uri' => '/test?access_token='.$token,
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->repository->expects($this->any())->method('getByAccessToken')->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(sprintf('[ACCESS TOKEN] No access token found : "%s"', $token));

        $this->accessTokenAuthenticator->authenticate($request);
    }

    public function testAuthenticateFailBecauseTokenIsNotActive(): void
    {
        $user = UserEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()
            ->withHost(HostEntityBuilder::create()->withDomain('test.example.com')->build())
            ->withUser($user)
            ->build()
        ;

        $token = 'LZKvPzGgwvoGEiSrcjDoZH6PFr7K1ntj';
        $accessToken = AccessTokenEntityBuilder::create()
            ->withToken($token)
            ->withServer($server)
            ->withActive(false)
            ->build()
        ;

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'test.example.com',
            'X-Forwarded-Uri' => '/test?access_token='.$token,
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->repository->expects($this->any())->method('getByAccessToken')->willReturn($accessToken);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(sprintf('[ACCESS TOKEN] Expired token or not active token "%s"', $token));

        $result = $this->accessTokenAuthenticator->authenticate($request);
    }
}
