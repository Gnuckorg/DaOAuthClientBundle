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
 * IdentitySelectorInterface is the interface that class should
 * implement to be used as an identity selector.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
interface IdentitySelectorInterface
{
    /**
     * Set the identities.
     *
     * @param array $identities The identities.
     */
    function setIdentities(array $identities);

    /**
     * Select an identity.
     *
     * @param string $key The identifier key of the identity.
     */
    function select($key);

    /**
     * Get the id of the selectionned identity.
     *
     * @return string The id.
     */
    function getId();

    /**
     * Get the secret of the selectionned identity.
     *
     * @return string The secret.
     */
    function getSecret();
}