<?php

namespace Da\OAuthClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;

/**
 * @Route("/proxy")
 */
class ProxyController extends ContainerAware
{
    const RESPONSE_STATUS_OK = 'ok';
    const RESPONSE_STATUS_ERRORED = 'errored';

    /**
     * @Route("/login")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();
        $securityContext = $this->container->get('security.context');

        $defaultResourceOwnerName = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwnerName);

        // Format the auth request parameters.
        $requestParameters = $request->request->all();
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $errorUrl = $this->container->get('router')->generate('da_oauthclient_proxy_error', array(), true);
        $parsedErrorUrl = parse_url($errorUrl);
        $parameters = array(
            '_username'    => $requestParameters['_username'],
            '_password'    => $requestParameters['_password'],
            '_remember_me' => isset($requestParameters['_remember_me']) && $requestParameters['_remember_me'] ? true : false,
            'error_path'   => $parsedErrorUrl['path']
        );

        // Logout.
        $firewallName = $this->container->getParameter('hwi_oauth.firewall_name');
        $securityContext->setToken(null);
        $session->invalidate();
        
        // Replace login target path to avoid loading a page for nothing.
        $targetPathKey = sprintf('_security.%s.target_path', $firewallName);
        $targetPathBackup = $session->get($targetPathKey);
        $successUrl = $this->container->get('router')->generate('da_oauthclient_proxy_success', array(), true);
        $parsedSuccessUrl = parse_url($successUrl);
        $targetPath = preg_replace('/^\/[a-z_]*\.php/', '', $parsedSuccessUrl['path']);
        $session->set($targetPathKey, $targetPath);

        // Process auth request.
        $authUrl = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl($request, $defaultResourceOwner, null, $parameters);
        $response = $this->container->get('da_oauth_client.request.processor')->process($authUrl);
        $session->set($targetPathKey, $targetPathBackup);
        $content = array(
            'status' => ProxyController::RESPONSE_STATUS_OK,
            'content' => 'ok'
        );

        if (401 === $response->getStatusCode()) {
            $content = json_decode($response->getContent(), true);
        }

        // Re-up the security token.
        $token = $session->get('_security_'.$firewallName, null);
        if ($token) {
            $token = unserialize($token);
            $securityContext->setToken($token);
        }

        return $this->buildProxyResponse($content ? $content : array());
    }

    /**
     * @Route("/success")
     */
    public function successAction(Request $request)
    {
        return new Response('', 204);
    }

    /**
     * @Route("/error")
     */
    public function errorAction(Request $request)
    {
        $authError = $request->query->get('auth_error', '');

        $error = json_decode($authError, true);
        if (!$error) {
            $error = $authError;
        }

        $error = array(
            'status' => ProxyController::RESPONSE_STATUS_ERRORED,
            'content' => $error
        );

        return new Response(
            json_encode($error),
            401,
            array(
                'Content-Type: application/json'
            )
        );
    }

    /**
     * @Route("/registration")
     */
    public function registerAction(Request $request)
    {
        // TODO.
        /*$connect = $this->container->getParameter('hwi_oauth.connect');
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
        );*/
    }

    /**
     * @Route("/disconnect")
     * @Template()
     */
    public function disconnectAction(Request $request)
    {
        $disconnectionUrl = $this->container->get('router')->generate('disconnect');

        return new RedirectResponse($disconnectionUrl);
    }

    /**
     * Build the proxy response.
     *
     * @param array $content The content of the response.
     *
     * @return Response The response.
     */
    protected function buildProxyResponse(array $content)
    {
        $token = $this->container->get('security.context')->getToken();
        $statusCode = 200;

        if (null === $token || $token instanceof AnonymousToken || !$token->isAuthenticated()) {
            if (!isset($content['status']) || ProxyController::RESPONSE_STATUS_OK === $content['status']) {
                $content = array(
                    'status' => ProxyController::RESPONSE_STATUS_ERRORED,
                    'content' => 'security.login.fail'
                );
            }
        }

        $content['content'] = $this->translate($content['content']);

        if (ProxyController::RESPONSE_STATUS_ERRORED === $content['status']) {
            $statusCode = 401;
        }

        return new Response(
            json_encode($content),
            $statusCode,
            array(
                'Content-Type: application/json'
            )
        );
    }

    /**
     * Translate.
     *
     * @param string|array The object of the translation.
     *
     * @return string|array The translation.
     */
    protected function translate($object)
    {
        if (is_string($object)) {
            $object = $this->container->get('translator')->trans(
                $object,
                array(),
                'DaOAuthClientBundle'
            );
        } else {
            foreach ($object as $key => $value) {
                $object[$key] = $this->translate($object);
            }
        }

        return $object;
    }
}
