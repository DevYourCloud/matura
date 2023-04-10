<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExternalAuthController extends AbstractController
{
    /**
     * @Route("/auth", name="app_request_auth")
     */
    public function requestAuthenticationAction(): Response
    {
        return new Response();
    }
}
