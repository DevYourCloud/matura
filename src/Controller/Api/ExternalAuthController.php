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
        $device = null;
        if ($appContext->hasValidForwardedAuthRequest()) {
            $device = $appContext->getConnectedDevice();
        }

        return new Response(
            $this->renderView('security/unauthorized_access.html.twig', [
                'device' => $device,
                'appHost' => $appHost,
                'isNewDevice' => $appContext->createTrustedCookie(),
            ]),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
