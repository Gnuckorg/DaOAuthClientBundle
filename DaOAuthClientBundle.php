<?php

/**
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Da\OAuthClientBundle\DependencyInjection\DaOAuthClientExtension;

class DaOAuthClientBundle extends Bundle
{
    public function __construct()
    {
        $this->extension = new DaOAuthClientExtension();
    }
}
