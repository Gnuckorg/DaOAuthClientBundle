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

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseUserProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use FOS\UserBundle\Model\UserManagerInterface;

/**
 * FOSUBUserProvider
 *
 * @author Thomas Prelot
 */
class FOSUBUserProvider extends BaseUserProvider
{
    /**
     * The class' name of the user.
     *
     * @var string
     */
    private $userClassName;

    /**
     * Should persist the user?
     *
     * @var boolean
     */
    private $persistUser;

    /**
     * Constructor.
     *
     * @param UserManagerInterface $userManager   FOSUB user provider.
     * @param array                $properties    Property mapping.
     * @param string               $userClassName The class' name of the user.
     * @param boolean              $persistUser   Should persist the user?
     */
    public function __construct(UserManagerInterface $userManager, array $properties, $userClassName, $persistUser = false)
    {
        parent::__construct($userManager, $properties);
 
        $this->userClassName = $userClassName;
        $this->persistUser = $persistUser;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        if ($this->persistUser) {
            $user = $this->userManager->findUserByEmail($response->getEmail());
        }

        if (!isset($user)) {
            $user = new $this->userClassName();
            if ($user instanceof OAuthUserInterface)
                $user->setFromResponse($response);
            
            if ($this->persistUser) {
                $this->userManager->updateUser($user);
            }
        }

        return $user;
    }
}
