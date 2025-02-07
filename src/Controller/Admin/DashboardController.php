<?php

namespace App\Controller\Admin;

use App\Entity\AccessToken;
use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Admin\AccessCodeFormType;
use App\Repository\ConnectedDeviceRepository;
use App\Service\ConnectedDeviceManager;
use App\Service\NameGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private ConnectedDeviceManager $connectedDeviceManager,
        private ConnectedDeviceRepository $connectedDeviceRepository,
        private NameGeneratorService $nameGeneratorService,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        $form = $this->createForm(AccessCodeFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $accessCode = \ltrim(trim((string) $form->get(AccessCodeFormType::FIELD_CODE)->getData()));

            $device = $this->connectedDeviceManager->validateAccessCode($accessCode);

            if ($device instanceof ConnectedDevice) {
                if (null === $device->getName()) {
                    $device->setName($this->nameGeneratorService->getRandomName());
                }

                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('app.admin.flash.device_validated', ['%name%' => $device->getName()]));
            } else {
                $this->addFlash('warning', $this->translator->trans('app.admin.flash.device_not_found'));
            }
        }

        $lastActiveDevices = $this->connectedDeviceRepository->getLastActiveDevices();

        return $this->render('admin/dashboard.html.twig', [
            'form' => $form,
            'user' => $this->getUser(),
            'lastActiveDevices' => $lastActiveDevices,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Matura')
            ->setFaviconPath('favicon.ico')
        ;
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linktoDashboard('Dashboard', 'fa fa-home'),
            MenuItem::linkToCrud('Servers', 'fa fa-server', Server::class),
            MenuItem::linkToCrud('Apps', 'fa fa-shapes', Application::class),
            MenuItem::linkToCrud('Connected Devices', 'fa fa-computer', ConnectedDevice::class),
            MenuItem::linkToCrud('User', 'fa fa-user', User::class),
            MenuItem::linkToCrud('Access Token', 'fa fa-key', AccessToken::class),
        ];
    }
}
