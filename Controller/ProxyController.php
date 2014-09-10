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
     * @Template()
     */
    public function disconnectAction(Request $request)
    {
        $disconnectionUrl = $this->container->get('router')->generate('disconnect');

        return new RedirectResponse($disconnectionUrl);
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

        $defaultResourceOwnerName = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$defaultResourceOwnerName);

        // Format the auth request parameters.
        $requestParameters = $request->request->all();
        if (!empty($formName)) {
            $requestParameters = array(
                $formName => $requestParameters,
                'form_name' => $formName
            );
        }
        $defaultResourceOwner = $this->container->getParameter('da_oauth_client.default_resource_owner');
        $errorUrl = $this->container->get('router')->generate('da_oauthclient_proxy_error', array(), true);
        $parsedErrorUrl = parse_url($errorUrl);
        $parameters = array_merge(
            $requestParameters,
            array('error_path' => $parsedErrorUrl['path']),
            $additionalParameters
        );

        // Logout.
        if ($logout) {
            $securityContext->setToken(null);
            $session->invalidate();
        }
        
        // Replace login target path to avoid loading a page for nothing.
        $firewallName = $this->container->getParameter('hwi_oauth.firewall_name');
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
        $statusCode = $response->getStatusCode();

        if (400 <= $statusCode) {
            $content = json_decode($response->getContent(), true);
        }

        // Re-up the security token.
        $token = $session->get('_security_'.$firewallName, null);
        if ($token) {
            $token = unserialize($token);
            $securityContext->setToken($token);
        }

        return $this->buildProxyResponse($statusCode, $content ? $content : array());
    }

    /**
     * Build the proxy response.
     *
     * @param integer $statusCode The HTTP status code.
     * @param array   $content    The content of the response.
     *
     * @return Response The response.
     */
    protected function buildProxyResponse($statusCode = 200, array $content = array())
    {
        $token = $this->container->get('security.context')->getToken();

        if (200 === $statusCode) {
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

            if (!isset($content['content'])) {
                $content = array(
                    'status' => ProxyController::RESPONSE_STATUS_ERRORED,
                    'content' => 'security.error'
                );
            }
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
