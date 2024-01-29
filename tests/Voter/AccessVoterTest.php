<?php

namespace App\Tests\Voter;

use App\Context\AppContext;
use App\Model\ForwardedRequest;
use App\Tests\Builder\ConnectedDeviceEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Builder\UserEntityBuilder;
use App\Tests\Mock\HostRepositoryMock;
use App\Voter\AccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AccessVoterTest extends TestCase
{
    private HostRepositoryMock $hostRepository;
    private AppContext $appContext;
    private AccessVoter $voter;

    public function setUp(): void
    {
        $this->hostRepository = new HostRepositoryMock();
        $this->appContext = ServiceBuilder::getAppContext($this->hostRepository);

        $this->voter = new AccessVoter($this->appContext);
    }

    public function testSupportAccess(): void
    {
        self::assertTrue($this->voter->supportsAttribute(AccessVoter::ACCESS_ATTR));
    }

    public function testAccessGranted(): void
    {
        $host = HostEntityBuilder::create()->withDomain('example.test.com')->build();
        $user = UserEntityBuilder::create()->withRoles(['ROLE_ADMIN'])->build();
        $server = ServerEntityBuilder::create()->withHost($host)->withUser($user)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()->withActive(true)->withUser($user)->withServer($server)->build();

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $server->getHost()->getDomain(),
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));

        $token = new UsernamePasswordToken($user, 'test_firewall');

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $connectedDevice, [AccessVoter::ACCESS_ATTR]));
    }

    public function testAccessDeniedOnDeviceInactive(): void
    {
        $host = HostEntityBuilder::create()->withDomain('example.test.com')->build();
        $user = UserEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withHost($host)->withUser($user)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()->withActive(false)->withUser($user)->withServer($server)->build();

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $server->getHost()->getDomain(),
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));
        $token = new UsernamePasswordToken($user, 'test_firewall');

        self::assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $connectedDevice, [AccessVoter::ACCESS_ATTR]));
    }

    public function testAccessDeniedIfUserIsNotAdmin(): void
    {
        $host = HostEntityBuilder::create()->withDomain('example.test.com')->build();
        $user = UserEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withHost($host)->withUser($user)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()->withActive(true)->withUser($user)->withServer($server)->build();

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $server->getHost()->getDomain(),
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));
        $token = new UsernamePasswordToken($user, 'test_firewall');

        self::assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $connectedDevice, [AccessVoter::ACCESS_ATTR]));
    }
}
