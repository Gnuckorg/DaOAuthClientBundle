<?php

namespace Da\OAuthClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;

class ConnectController extends BaseConnectController
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

        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');

        return new RedirectResponse($this->container->get('router')->generate('hwi_oauth_service_redirect', array('service' => $defaultResourceOwner)), 302);
    }

    /**
     * @Route("/login/fwd")
     */
    public function loginFwdAction(Request $request)
    {
        $connect = $this->container->getParameter('hwi_oauth.connect');
        $session = $request->getSession();
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');

        $error = $this->getErrorForRequest($request);

        $loginTemplate = $this->container->getParameter('da_oauth_client.login_template');
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwner);
        $redirectUri = $request->query->get('redirect_uri');
        $authUrl = $resourceOwner->getAuthorizationUrl($redirectUri);
        $authError = $request->query->get('auth_error', '');

        if (!empty($authError)) {
            $this->container->get('session')->getFlashBag()->add(
                'error',
                $authError
            );
        }

        return $this->container->get('templating')->renderResponse($loginTemplate, array(
            // Last username entered by the user.
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'auth_url'      => $authUrl,
            'csrf_token'    => $request->query->get('csrf_token'),
            'redirect_uri'  => $redirectUri
        ));
    }

    /**
     * @Route("/disconnect", name="disconnect")
     * @Template()
     */
    public function disconnectAction(Request $request)
    {
        $token = $this->container->get('security.context')->getToken();
        $resourceOwnerName = $token->getResourceOwnerName();
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$resourceOwnerName);

        $disconnectionUrl = $resourceOwner->getDisconnectionUrl(
            $this->container->get('router')->generate('logout', array(), true)
        );

        return new RedirectResponse($disconnectionUrl);
    }
}
