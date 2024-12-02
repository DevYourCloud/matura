<?php

namespace App\Tests\EventListener;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use App\EventListener\OnKernelRequestCreateDevice;
use App\Model\ForwardedRequest;
use App\Tests\Builder\ApplicationEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Builder\UserEntityBuilder;
use App\Tests\Mock\HostRepositoryMock;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class OnKernelRequestCreateDeviceTest extends TestCase
{
    private HostRepositoryMock $hostRepository;

    private AppContext $appContext;

    private OnKernelRequestCreateDevice $listener;

    public function setUp(): void
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->hostRepository = new HostRepositoryMock();
        $this->appContext = ServiceBuilder::getAppContext($this->hostRepository);
        $encryptionService = ServiceBuilder::getEncryptionService();
        $factory = ServiceBuilder::getConnectedDeviceFactory(
            $encryptionService,
            $entityManager
        );

        $this->listener = ServiceBuilder::getOnKernelRequestCreateDeviceListener($this->appContext, $factory);
    }

    public function testDeviceCreation(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $domain = 'test.example.com';

        $user = UserEntityBuilder::create()->build();
        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(true)->withUser($user)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;

        $request = new Request(
            [], [], [], [], [], []
        );

        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->hostRepository->setHost($host);

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));
        $this->appContext->setCreateTrustedCookie(true);

        $this->listener->__invoke(new RequestEvent(
            $httpKernel,
            new Request(),
            1,
        ));

        self::assertInstanceOf(ConnectedDevice::class, $this->appContext->getConnectedDevice());
        self::assertNotEmpty($this->appContext->getConnectedDevice()->getHash());
    }

    public function testNoDeviceCreationWhenPairingDisabled(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $domain = 'test.example.com';

        $user = UserEntityBuilder::create()->build();
        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(false)->withUser($user)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;

        $request = new Request(
            [], [], [], [], [], []
        );

        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->hostRepository->setHost($host);

        $this->appContext->initializeFromRequest(new ForwardedRequest($request));
        $this->appContext->setCreateTrustedCookie(true);

        $this->listener->__invoke(new RequestEvent(
            $httpKernel,
            new Request(),
            1,
        ));

        self::assertNull($this->appContext->getConnectedDevice());
    }

    public function testNoDeviceCreationWhenNoCookieCreation(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $this->appContext->setCreateTrustedCookie(false);

        $this->listener->__invoke(new RequestEvent(
            $httpKernel,
            new Request(),
            1,
        ));

        self::assertNull($this->appContext->getConnectedDevice());
    }

    public function testNoDeviceCreationWhenHavingADevice(): void
    {
        /** @var HttpKernelInterface|MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $initialHash = 'TEST';
        $connectedDevice = new ConnectedDevice();
        $connectedDevice->setHash($initialHash);

        $this->appContext->setCreateTrustedCookie(true);
        $this->appContext->setConnectedDevice($connectedDevice);

        $this->listener->__invoke(new RequestEvent(
            $httpKernel,
            new Request(),
            1,
        ));

        self::assertEquals($initialHash, $this->appContext->getConnectedDevice()->getHash());
    }
}
