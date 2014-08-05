<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
//use Da\OAuthClientBundle\OAuth\ResourceOwner\MultiTokensResourceOwnerInterface;

/**
 * InjectIdentitySelectorPass allow to inject the identity selector to
 * resource owners.
 *
 * @author Thomas Prelot
 */
class InjectIdentitySelectorPass implements CompilerPassInterface
{
    /**
     * Process the ContainerBuilder to inject the configuration and the implementor
     * into the API client.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $resourceOwners = $container->getParameter('hwi_oauth.resource_owners');

        foreach ($resourceOwners as $name) {
            $resourceOwner = $container->getDefinition(
                sprintf('hwi_oauth.resource_owner.%s', $name)
            );

            $class = new \ReflectionClass($resourceOwner->getClass());

            if ($class->implementsInterface('Da\OAuthClientBundle\OAuth\ResourceOwner\MultiTokensResourceOwnerInterface')) {
                $options = $resourceOwner->getArgument(2);

                if (isset($options['identity'])) {
                    $resourceOwner->addArgument(
                        new Reference($options['identity']['selector'])
                    );
                }
            }
        }
    }
}
