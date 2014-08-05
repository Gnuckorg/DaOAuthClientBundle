<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\Identity;

/**
 * BasicIdentitySelector is a basic implementation
 * of an identity selector.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
class BasicIdentitySelector implements IdentitySelectorInterface
{
    /**
     * The possible identities.
     *
     * @var array
     */
    protected $identities = array();

    /**
     * The current selected identity.
     *
     * @var array
     */
    protected $identity;

    /**
     * {@inheritDoc}
     */
    public function setIdentities(array $identities)
    {
        $this->identities = $identities['tokens'];
        $defaultIdentity = $identities['default_tokens'];

        if ($defaultIdentity) {
            $this->select($defaultIdentity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function select($key)
    {
        if (!isset($this->identities[$key])) {
            throw new \LogicException(sprintf(
                'The key "%s" is not defined in the list of the identities.',
                $key
            ));
        }

        $this->identity = $this->identities[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->identity['client_id'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSecret()
    {
        return $this->identity['client_secret'];
    }
}