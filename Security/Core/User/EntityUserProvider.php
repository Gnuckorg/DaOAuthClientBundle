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

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\EntityUserProvider as BaseUserProvider;

/**
 * OAuthUserProvider
 *
 * @author Thomas Prelot
 */
class EntityUserProvider extends BaseUserProvider
{
    /**
     * The entity manager.
     *
     * @var object
     */
    protected $em;

    /**
     * The user entity class to load.
     *
     * @var string
     */
    protected $class;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry    Manager registry.
     * @param string          $class       User entity class to load.
     * @param array           $properties  Mapping of resource owners to properties
     * @param string          $managerName Optional name of the entitymanager to use
     */
    public function __construct(ManagerRegistry $registry, $class, array $properties, $managerName = null)
    {
        $this->em = $registry->getManager($managerName);
        $this->class = $class;
        parent::__construct($registry, $class, $properties, $managerName);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        try
        {
            $user = parent::loadUserByOAuthUserResponse($response);
        }
        catch (UsernameNotFoundException $e)
        {
            $user = new $this->class();
            if ($user instanceof OAuthUserInterface)
                $user->setFromResponse($response);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}
