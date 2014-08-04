<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\OAuth\ResourceOwner;

use Da\OAuthClientBundle\Identity\IdentitySelectorInterface;

/**
 * MultiTokensResourceOwnerInterface is the interface that a resource owner
 * should implement to use multi tokening.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
interface MultiTokensResourceOwnerInterface
{
    /**
     * Set the identity selector.
     */
    function setIdentitySelector(IdentitySelectorInterface $identitySelector);
}