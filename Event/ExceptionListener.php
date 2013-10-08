<?php

namespace Da\OAuthClientBundle\Event;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Da\AuthCommonBundle\Exception\ApiHttpResponseException;
use Da\AuthCommonBundle\Security\AuthorizationRefresherInterface;

/**
 * Listener to pass the api http exceptions.
 */
class ExceptionListener
{
    /**
     * The HTTP kernel.
     *
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * The authorization refresher.
     *
     * @var AuthorizationRefresherInterface
     */
    protected $authorizationRefresher;

    /**
     * Constructor
     *
     * @param HttpKernelInterface             $kernel                 The HTTP kernel.
     * @param AuthorizationRefresherInterface $authorizationRefresher The authorization refresher.
     */
    public function __construct(HttpKernelInterface $kernel, AuthorizationRefresherInterface $authorizationRefresher)
    {
        $this->kernel = $kernel;
        $this->authorizationRefresher = $authorizationRefresher;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception =  $event->getException();

        if ($exception instanceof ApiHttpResponseException) {
            if (401 === $exception->getStatusCode() && $exception->isFirstTry()) {
                // Retry the request after refreshing the authorization.
                // Master request because we need to reload the user.
                $this->authorizationRefresher->refresh();
                $response = $this->kernel->handle($event->getRequest());
            } else {
                $response = new Response();
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
                $response->headers->set('Content-Type', 'application/json');
                $response->setContent($exception->getJsonMessage());
            }

            $event->setResponse($response);
        }
    }
}