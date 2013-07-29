<?php

namespace Tessi\OAuthClientBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;

/**
 * OAuthUserInterface is an interface that a user should implement to be 
 * used at full potential by the entity user provider.
 *
 * @author Thomas Prelot
 */
interface OAuthUserInterface
{
    /**
     * Set the user from the oauth response.
     *
     * @param LocalUserInterface The response from the oauth processus.
     */
    function setFromResponse(UserResponseInterface $response);
}
