<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Admin\AccessCodeFormType;
use App\Service\ConnectedDeviceManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private ConnectedDeviceManager $connectedDeviceManager
    ) {}

    #[Route(path: '/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        $form = $this->createForm(AccessCodeFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $accessCode = (string) $form->get(AccessCodeFormType::FIELD_NAME)->getData();

            if ($this->connectedDeviceManager->validateAccessCode($accessCode)) {
                $this->em->flush();
                $this->addFlash('success', 'Device validated');
            } else {
                $this->addFlash('error', 'Device not found');
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'form' => $form,
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
        ];
    }
}
