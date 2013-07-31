<?php

/**
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use HWI\Bundle\OAuthBundle\DependencyInjection\HWIOAuthExtension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DaOAuthClientExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $resourceOwners = $container->getParameter('hwi_oauth.resource_owners');
        $hwiExtension = new HWIOAuthExtension();
        foreach ($config['resource_owners'] as $name => $options) {
            $resourceOwners[] = $name;
            $hwiExtension->createResourceOwnerService($container, $name, $options);
        }
        $container->setParameter('hwi_oauth.resource_owners', $resourceOwners);

        if (isset($config['fosub'])) {
            $container
                ->getDefinition('da_oauth_client.user_provider')
                    ->replaceArgument(1, $config['fosub']['properties']);
            ;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'da_oauth_client';
    }
}
