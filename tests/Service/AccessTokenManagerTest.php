<?php

namespace App\Tests\Service;

use App\Entity\AccessToken;
use App\Entity\Server;
use App\Model\TokenValidityPeriod;
use App\Service\AccessTokenManager;
use App\Service\EncryptionService;
use App\Tests\Builder\AccessTokenEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use PHPUnit\Framework\TestCase;

class AccessTokenManagerTest extends TestCase
{
    private AccessTokenManager $accessTokenManager;

    public function setUp(): void
    {
        $this->accessTokenManager = new AccessTokenManager(ServiceBuilder::getEncryptionService(), 'access_token');
    }

    /**
     * @dataProvider validityPeriodDataProvider
     */
    public function testCreateAccessToken(?TokenValidityPeriod $period): void
    {
        $accessToken = new AccessToken();
        $accessToken
            ->setName('Test')
            ->setValidityPeriod($period->value)
            ->setServer(ServerEntityBuilder::create()->withHost(
                HostEntityBuilder::create()->withDomain('test.example.com')->build())->build()
            )
        ;

        $this->accessTokenManager->generateAccessTokenData($accessToken);

        self::assertEquals('Test', $accessToken->getName());
        self::assertNotNull($accessToken->getAccessToken());
        self::assertEquals(EncryptionService::ACCESS_TOKEN_LENGTH, strlen($accessToken->getAccessToken()));

        $date = new \DateTime();
        $date->setTime(0, 0, 0);
        $date->add(new \DateInterval('P'.($period->value + 1).'D'));

        self::assertEquals($date, $accessToken->getValidity());
        self::assertTrue($accessToken->isActive());
        self::assertInstanceOf(Server::class, $accessToken->getServer());
    }

    public function testNullValidityPeriod(): void
    {
        $accessToken = new AccessToken();
        $accessToken
            ->setName('Test')
            ->setValidityPeriod(null)
            ->setServer(ServerEntityBuilder::create()->withHost(
                HostEntityBuilder::create()->withDomain('test.example.com')->build())->build()
            )
        ;

        $this->accessTokenManager->generateAccessTokenData($accessToken);

        self::assertEquals('Test', $accessToken->getName());
        self::assertNotNull($accessToken->getAccessToken());
        self::assertEquals(EncryptionService::ACCESS_TOKEN_LENGTH, strlen($accessToken->getAccessToken()));

        self::assertTrue($accessToken->isActive());
        self::assertInstanceOf(Server::class, $accessToken->getServer());
        self::assertNull($accessToken->getValidity());
    }

    public function validityPeriodDataProvider(): array
    {
        return [
            'case SEVEN_DAYS' => [TokenValidityPeriod::SEVEN_DAYS],
            'case THIRTY_DAYS' => [TokenValidityPeriod::THIRTY_DAYS],
            'case NINETY_DAYS' => [TokenValidityPeriod::NINETY_DAYS],
            'case SIX_MONTHS' => [TokenValidityPeriod::SIX_MONTHS],
            'case ONE_YEAR' => [TokenValidityPeriod::ONE_YEAR],
        ];
    }

    public function testGetTokenUrl(): void
    {
        $accessToken = AccessTokenEntityBuilder::create()
            ->withServer(
                ServerEntityBuilder::create()->withHost(
                    HostEntityBuilder::create()->withDomain('test.example.com')->build()
                )->build()
            )
            ->withToken('ABCD')
            ->build()
        ;

        $url = $this->accessTokenManager->getTokenUrl($accessToken);

        self::assertEquals('https://test.example.com?access_token=ABCD', $url);
    }

    /**
     * @dataProvider validityTokenDataProvider
     */
    public function testAccessTokenValidity(?\DateTime $validityDate, bool $isValid, bool $isServerActive, bool $isTokenActive): void
    {
        $server = ServerEntityBuilder::create()
            ->withHost(HostEntityBuilder::create()->withDomain('test.example.com')->build())
            ->withActive($isServerActive)
            ->build()
        ;

        $accessToken = AccessTokenEntityBuilder::create()
            ->withServer($server)
            ->withValidity($validityDate)
            ->withToken('ABCD')
            ->withActive($isTokenActive)
            ->build()
        ;

        self::assertEquals($isValid, $this->accessTokenManager->isValidAccessToken($accessToken));
    }

    public function validityTokenDataProvider(): array
    {
        $expirationDateTomorrow = new \DateTime();
        $expirationDateTomorrow->modify('+1 day');
        $expirationDateTomorrow->setTime(0, 0, 0);

        $expirationDateYesterday = new \DateTime();
        $expirationDateYesterday->modify('-1 day');
        $expirationDateYesterday->setTime(0, 0, 0);

        $expirationDateToday = new \DateTime();
        $expirationDateToday->setTime(0, 0, 0);

        return [
            'Expiration is tomorrow' => [$expirationDateTomorrow, true, true, true],
            'Expiration is yesterday' => [$expirationDateYesterday, false, true, true],
            'Expiration is today' => [$expirationDateYesterday, false, true, true],
            'Expiration is tomorrow but server is disabled' => [$expirationDateTomorrow, false, false, true],
            'Expiration is yesterday but server is disabled' => [$expirationDateYesterday, false, false, true],
            'Token never expire' => [null, true, true, true],
            'Token never expire but server is disabled' => [null, false, false, true],
            'Expiration is tomorrow but token is disabled' => [$expirationDateTomorrow, false, true, false],
        ];
    }
}
