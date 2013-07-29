<?php

namespace Tessi\OAuthClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;

class MainController extends ContainerAware
{
    /**
     * @Route("/login")
     * @Template()
     */
    public function loginAction(Request $request)
    {
    	$connect = $this->container->getParameter('hwi_oauth.connect');
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');

        $error = $this->getErrorForRequest($request);

        // if connecting is enabled and there is no user, redirect to the registration form
        if ($connect
            && !$hasUser
            && $error instanceof AccountNotLinkedException
        ) {
            $key = time();
            $session = $request->getSession();
            $session->set('_hwi_oauth.registration_error.'.$key, $error);

            return new RedirectResponse($this->generate('hwi_oauth_connect_registration', array('key' => $key)));
        }

    	return new RedirectResponse($this->container->get('router')->generate('hwi_oauth_service_redirect', array('service' => 'tessi')), 302);
    }

    /**
     * Get the security error for a given request. DUPLICATED METHOD OF THE HWI CONNECTCONTROLLER.
     *
     * @param Request $request
     *
     * @return string|\Exception
     */
    protected function getErrorForRequest(Request $request)
    {
        $session = $request->getSession();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        return $error;
    }
}
