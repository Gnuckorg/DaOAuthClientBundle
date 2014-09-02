<?php

namespace Da\OAuthClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerAware;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;

/**
 * @Route("/proxy")
 */
class ProxyController extends ContainerAware
{
    /**
     * @Route("/login")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        $defaultResourceOwnerName = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwnerName);

        $requestParameters = $request->request->all();
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        //$redirectUri = $this->container->get('router')->generate('da_oauthclient_proxy_loginresponse', array(), true);
        $parameters = array(
            '_username'      => $requestParameters['_username'],
            '_password'      => $requestParameters['_password'],
            '_remember_me'   => isset($requestParameters['_remember_me']) && $requestParameters['_remember_me'] ? true : false,
            //'redirect_uri'   => $redirectUri
        );

        $authUrl = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl($request, $defaultResourceOwner, null, $parameters);
        /*$response = */$this->container->get('da_oauth_client.request.processor')->process($authUrl);

        return new RedirectResponse($this->container->get('router')->generate('da_oauthclient_proxy_loginresponse'));
    }

    /**
     * @Route("/test")
     * @Template()
     */
    public function testAction(Request $request)
    {
        return array();
    }

    /**
     * @Route("/login/response")
     */
    public function loginResponseAction(Request $request)
    {
        var_dump($this->container->get('security.context')->getToken());
        return new Response('abc');
    }

    /**
     * @Route("/registration")
     */
    public function registerAction(Request $request)
    {
        $connect = $this->container->getParameter('hwi_oauth.connect');
        $session = $request->getSession();
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');

        $error = $this->getErrorForRequest($request);

        $registrationTemplate = $this->container->getParameter('da_oauth_client.registration_template');
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwner);
        $redirectUri = $request->query->get('redirect_uri');
        $authUrl = $resourceOwner->getAuthorizationUrl($redirectUri);
        $authError = $request->query->get('auth_error', '');

        $parameters = array(
            'auth_url'      => $authUrl,
            'csrf_token'    => $request->query->get('csrf_token', null),
            'redirect_uri'  => $redirectUri
        );

        return $this->container->get('templating')->renderResponse(
            $registrationTemplate,
            array_merge(
                array(
                    'error'     => $error,
                    'registration_error' => json_decode($authError, true),
                    'login_url' => $this->container->get('router')->generate(
                        'da_oauthclient_connect_loginfwd',
                        $parameters
                    ),
                    'form_cached_values' => $request->query->get('form_cached_values', array())
                ),
                $parameters
            )
        );
    }

    /**
     * @Route("/disconnect")
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
