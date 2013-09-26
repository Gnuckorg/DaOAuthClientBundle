<?php

namespace Da\OAuthClientBundle\Entity;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Da\OAuthClientBundle\Security\Core\User\OAuthUserInterface;

/**
 * User is the user implementation used by the memory user provider.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
class MemoryUser implements AdvancedUserInterface, OAuthUserInterface
{
    protected $username = '';
    protected $email = '';
    protected $password = '';
    protected $enabled = true;
    protected $accountNonExpired = true;
    protected $credentialsNonExpired = true;
    protected $accountNonLocked = true;
    protected $roles = array();

    /**
     * {@inheritDoc}
     */
    public function setFromResponse(UserResponseInterface $response)
    {
        $this->username = $response->getNickname();
		$this->email = $response->getEmail();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the email of the user.
     *
     * @return string The email.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return $this->accountNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
