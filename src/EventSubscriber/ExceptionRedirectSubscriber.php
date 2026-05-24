<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        if (str_starts_with($pathInfo, '/api/') || str_starts_with($pathInfo, '/error/')) {
            return;
        }

        $throwable = $event->getThrowable();
        $statusCode = 500;

        if ($throwable instanceof AccessDeniedHttpException || $throwable instanceof AccessDeniedException) {
            $statusCode = 403;
        } elseif ($throwable instanceof HttpExceptionInterface) {
            $statusCode = $throwable->getStatusCode();
        }

        if (!in_array($statusCode, [403, 404, 500], true)) {
            $statusCode = 500;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_error_show', ['code' => $statusCode])));
    }
}