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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Buzz\Client\ClientInterface as HttpClientInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GenericOAuth2ResourceOwner;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use Da\OAuthClientBundle\Identity\IdentitySelectorInterface;

/**
 * MultiTokensOAuth2ResourceOwner
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
class MultiTokensOAuth2ResourceOwner extends GenericOAuth2ResourceOwner implements MultiTokensResourceOwnerInterface
{
    /**
     * @param HttpClientInterface         $httpClient       Buzz http client
     * @param HttpUtils                   $httpUtils        Http utils
     * @param array                       $options          Options for the resource owner
     * @param string                      $name             Name for the resource owner
     * @param RequestDataStorageInterface $storage          Request token storage
     * @param IdentitySelectorInterface   $identitySelector Identity selector
     */
    public function __construct(
        HttpClientInterface $httpClient,
        HttpUtils $httpUtils,
        array $options,
        $name,
        RequestDataStorageInterface $storage,
        IdentitySelectorInterface $identitySelector
    )
    {
        $this->identitySelector = $identitySelector;

        if ($options['identity']) {
            $this->identitySelector->setIdentities($options['identity']);
            $options['client_id'] = $this->identitySelector->getId();
            $options['client_secret'] = $this->identitySelector->getSecret();

            unset($options['identity']);
        }

        parent::__construct($httpClient, $httpUtils, $options, $name, $storage);
    }

    /**
     * {@inheritDoc}
     */
    public function setIdentitySelector(IdentitySelectorInterface $identitySelector)
    {
        $this->identitySelector = $identitySelector;
    }
}
