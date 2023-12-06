<?php

namespace App\Controller\Api;

use App\Context\AppContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExternalAuthController extends AbstractController
{
    #[Route(path: '/auth', name: 'app_request_auth')]
    public function requestAuthenticationAction(AppContext $appContext, string $appHost): Response
    {
        return new Response(
            $this->renderView('security/unauthorized_access.html.twig', [
                'isPairing' => (bool) $appContext->getServer()?->isPairing(),
                'device' => $appContext->getConnectedDevice(),
                'appHost' => $appHost,
            ]),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
