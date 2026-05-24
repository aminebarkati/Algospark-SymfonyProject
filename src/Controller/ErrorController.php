<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    #[Route('/error/{code}', name: 'app_error_show', requirements: ['code' => '\\d+'])]
    public function show(int $code): Response
    {
        $template = match ($code) {
            403 => 'bundles/TwigBundle/Exception/error403.html.twig',
            404 => 'bundles/TwigBundle/Exception/error404.html.twig',
            default => 'bundles/TwigBundle/Exception/error500.html.twig',
        };

        return $this->render($template, ['status_code' => $code], new Response('', $code));
    }
}