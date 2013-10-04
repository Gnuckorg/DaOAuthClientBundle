<?php

/**
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils;
use Da\AuthCommonBundle\Security\AuthorizationRefresherInterface;

/**
 * The oauth authorization refresher.
 *
 * @author Thomas Prelot
 */
class OAuthRefresher implements AuthorizationRefresherInterface
{
    /**
     * The services' container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container) 
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        $token = $this->container->get('security.context')->getToken();
        $resourceOwnerName = $token->getResourceOwnerName();
        $refreshToken = $token->getRefreshToken();

        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$resourceOwnerName);
        $response = $resourceOwner->refreshAccessToken($refreshToken);

        if (!is_array($response)) {
            $token->setAccessToken($accessToken);
        } else {
            $token->setAccessToken($response['access_token']);
            $token->setRefreshToken($response['refresh_token']);
            $raw = $token->getRawToken();
            $raw['access_token'] = $response['access_token'];
            $raw['refresh_token'] = $response['refresh_token'];
            $token->setRawToken($raw);
            //$token->setRoles($response['scope']); // TODO
        }

        $session = $this->container->get('session');
        $firewallName = $this->container->getParameter('hwi_oauth.firewall_name');
        $session->set('_security_'.$firewallName, serialize($token));
        $session->save();
    }
}