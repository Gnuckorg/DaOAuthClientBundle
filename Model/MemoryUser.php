<?php

namespace Da\OAuthClientBundle\Model;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Da\OAuthClientBundle\Security\Core\User\OAuthUserInterface;

/**
 * MemoryUser is the user implementation used by the memory user provider.
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
    protected $raw = array();

    /**
     * {@inheritDoc}
     */
    public function setFromResponse(UserResponseInterface $response)
    {
        $this->username = $response->getNickname();
		$this->email = $response->getEmail();
        $this->roles = json_decode($response->getRoles(), true);
        if (!is_array($this->roles)) {
            $this->roles = array();
        }
        $this->raw = json_decode($response->getRaw(), true);
        if (!is_array($this->raw)) {
            $this->raw = array();
        }
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
     * Get the raw data of the user.
     *
     * @return The raw data.
     */
    public function getRaw()
    {
        return $this->raw;
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
