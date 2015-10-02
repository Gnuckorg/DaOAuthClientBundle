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
        return $this->processProxy($request);
    }

    /**
     * @Route("/register")
     */
    public function registerAction(Request $request)
    {
        return $this->processProxy(
            $request,
            array('account' => 'registration'),
            'da_oauth_registration_form',
            false
        );
    }

    /**
     * @Route("/profile")
     */
    public function profileAction(Request $request)
    {
        return $this->processProxy(
            $request,
            array('account' => 'profile'),
            'da_oauth_profile_form',
            false
        );
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
     * @Route("/disconnect")
     */
    public function disconnectAction(Request $request)
    {
        $session = $request->getSession();
        $securityContext = $this->container->get('security.context');
        $firewallName = $this->container->getParameter('hwi_oauth.firewall_name');

        $securityContext->setToken(null);
        $session->clear();

        return new Response('');
    }

    /**
     * Process the proxy.
     *
     * @param Request $request              The request.
     * @param array   $additionalParameters The optional additional parameters.
     *
     * @return Response The response.
     */
    protected function processProxy(
        Request $request,
        array $additionalParameters = array(),
        $formName = '',
        $logout = true
    )
    {
        $session = $request->getSession();
        $securityContext = $this->container->get('security.context');
        $firewallName = $this->container->getParameter('hwi_oauth.firewall_name');

        $defaultResourceOwnerName = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwnerName);

        // Format the auth request parameters.
        $requestParameters = $request->request->all();
        if (!empty($formName)) {
            $requestParameters = array(
                $formName => $this->formatFormParameters($requestParameters),
                'form_name' => $formName
            );
        }
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $errorUrl = $this->container->get('router')->generate('da_oauthclient_proxy_error', array(), true);
        $parsedErrorUrl = parse_url($errorUrl);
        $parameters = array_merge(
            $requestParameters,
            array(
                'error_path' => $parsedErrorUrl['path'],
                'logout' => 1
            ),
            $additionalParameters
        );

        // Logout.
        if ($logout) {
            $securityContext->setToken(null);
            //$session->invalidate();
            $session->clear();
        }

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
            'content' => $session->getId()
        );
        $statusCode = $response->getStatusCode();

        if (400 <= $statusCode) {
            $content = json_decode($response->getContent(), true);
        }

        if (null === $content) {
            $content = array();
        }

        if (!isset($content['content'])) {
            $content = array(
                'content' => sprintf('(%s) %s', $statusCode, $response->getContent())
            );
        }

        // Re-up the security token.
        $token = $session->get('_security_'.$firewallName, null);
        if ($token) {
            $token = unserialize($token);
            $securityContext->setToken($token);
        }

        return $this->buildProxyResponse($request, $statusCode, $content, $authUrl);
    }

    /**
     * Build the proxy response.
     *
     * @param Request $request    The request.
     * @param integer $statusCode The HTTP status code.
     * @param array   $content    The content of the response.
     * @param string  $authUrl    The auth URL.
     *
     * @return Response The response.
     */
    protected function buildProxyResponse(Request $request, $statusCode = 200, array $content = array(), $authUrl = '')
    {
        $token = $this->container->get('security.context')->getToken();

        if (400 > $statusCode) {
            if (null === $token || $token instanceof AnonymousToken || !$token->isAuthenticated()) {
                if (!isset($content['status']) || ProxyController::RESPONSE_STATUS_OK === $content['status']) {
                    $content = array(
                        'status' => ProxyController::RESPONSE_STATUS_ERRORED,
                        'content' => 'security.login.fail'
                    );

                    $statusCode = 401;
                }
            }
        }

        if (!isset($content['status']) ||
            (ProxyController::RESPONSE_STATUS_ERRORED === $content['status'] && $statusCode === 200)
        ) {
            $statusCode = 500;
            $content['status'] = $statusCode;

            if (!isset($content['content'])) {
                $content['content'] = 'security.error';
            }

            $this->container->get('logger')->error(
                'An error occured during a proxy attempt to the SSO',
                array(
                    'caller' => $request->getBaseUrl(),
                    'method' => $request->getMethod(),
                    'called' => $authUrl,
                    'response' => $content['content']
                )
            );
        }

        if (isset($content['content'])) {
            $content['content'] = $this->translate($content['content']);
        }

        return new Response(
            json_encode($content),
            $statusCode !== 204 ? $statusCode : 200,
            array(
                'Content-Type: application/json'
            )
        );
    }

    /**
     * Format the form parameters.
     *
     * @param array $parameters The input parameters.
     *
     * @return array The formatted parameters.
     */
    protected function formatFormParameters(array $parameters)
    {
        $mappedFields = array(
            'plainPassword_first' => 'plainPassword.first',
            'plainPassword_second' => 'plainPassword.second'
        );
        $standardFields = array(
            'username' => true,
            '_username' => true,
            'password' => true,
            '_password' => true,
            'password' => true,
            'current_password' => true,
            'email' => true,
            'plainPassword.first' => true,
            'plainPassword.second' => true,
            '_remember_me' => true,
            'remember_me' => true,
            'redirect_uri' => true,
            '_csrf_token' => true,
            'csrf_token' => true
        );
        $formattedParameters = array();
        $raw = isset($parameters['raw']) ? $parameters['raw'] : array();

        foreach ($parameters as $key => $value) {
            $realKey = isset($mappedFields[$key]) ? $mappedFields[$key] : $key;

            if (isset($standardFields[$realKey])) {
                $formattedParameters[$realKey] = $value;
            } else {
                $raw[$key] = $value;
            }
        }

        if (!empty($raw)) {
            $formattedParameters['raw'] = $raw;
        }

        return $formattedParameters;
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
                $object[$key] = $this->translate($value);
            }
        }

        return $object;
    }
}
