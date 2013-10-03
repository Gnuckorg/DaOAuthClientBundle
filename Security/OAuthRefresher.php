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
	 * The oauth utils.
     *
     * @var OAuthUtils
	 */
	protected $oauthUtils;

	/**
	 * The services' container.
     *
     * @var ContainerInterface
	 */
	protected $container;

    /**
     * Constructor.
     */
    public function __construct(OAuthUtils $oauthUtils, ContainerInterface $container) 
    {
    	$this->oauthUtils = $oauthUtils;
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
    	$resourceOwners = $this->oauthUtils->getResourceOwners();

    	$accessToken = $resourceOwners[$resourceOwnerName]->refreshAccessToken($refreshToken);

    	$token->setAccessToken($accessToken);
    }
}