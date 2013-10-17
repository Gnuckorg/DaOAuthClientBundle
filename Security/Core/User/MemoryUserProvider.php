<?php

/**
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\Security\Core\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;

/**
 * MemoryUserProvider
 *
 * @author Thomas Prelot
 */
class MemoryUserProvider implements OAuthAwareUserProviderInterface, UserProviderInterface
{
	/**
     * The user.
     *
     * @var UserInterface
     */
    private $user;

    /**
     * The class' name of the user.
     *
     * @var string
     */
    private $userClassName;

    /**
     * Constructor.
     *
     * @param string $userClassName The class' name of the user.
     */
    public function __construct($userClassName)
    {
        $this->userClassName = $userClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $this->user = new $this->userClassName();
        if ($this->user instanceof OAuthUserInterface) {
            $this->user->setFromResponse($response);
        }

        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
    	if (!$this->user) {
    		$this->user = new $this->userClassName();
    	}

    	return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        return $reflectionClass->isSubclassOf('\Da\OAuthClientBundle\Model\MemoryUser');
    }
}
