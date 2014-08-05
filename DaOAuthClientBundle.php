<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Da\OAuthClientBundle\DependencyInjection\DaOAuthClientExtension;
use Da\OAuthClientBundle\DependencyInjection\Compiler\InjectIdentitySelectorPass;

class DaOAuthClientBundle extends Bundle
{
    public function __construct()
    {
        $this->extension = new DaOAuthClientExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InjectIdentitySelectorPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
