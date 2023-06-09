<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin_dashboard")
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
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
