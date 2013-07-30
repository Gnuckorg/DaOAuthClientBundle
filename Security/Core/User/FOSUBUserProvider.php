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
use Da\OAuthClientBundle\Entity\User;

/**
 * OAuthUserProvider
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
     * Constructor.
     *
     * @param UserManagerInterface   $userManager   FOSUB user provider.
     * @param array                  $properties    Property mapping.
     * @param string                 $userClassName The class' name of the user.
     */
    public function __construct(UserManagerInterface $userManager, array $properties, $userClassName)
    {
        parent::__construct($userManager, $properties);
        
        $this->userClassName = $userClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $user = $this->userManager->findUserByEmail($response->getEmail());

        if (null === $user) 
        {
            $user = new $this->userClassName();
            if ($user instanceof OAuthUserInterface)
                $user->setFromResponse($response);
            $this->userManager->updateUser($user);
        }

        return $user;
    }
}
