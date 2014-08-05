DaOAuthClientBundle
===================

DaOAuthClientBundle is a Symfony2's bundle allowing to use an application as an oauth client.

Installation
------------

Installation is a quick 4 steps process!

### Step 1: Add in composer

Add the bundle and its dependencies in the composer.json file:

``` js
    // composer.json

    "require": {
        // ...
        "hwi/oauth-bundle": "0.3.*@dev",
        "da/auth-common-bundle": "dev-master",
        "da/oauth-client-bundle": "dev-master"
    },
```

If you want to persist the users with FOSUserBundle:

``` js
    // composer.json

    "require": {
        // ...
        "hwi/oauth-bundle": "0.3.*@dev",
        "da/auth-common-bundle": "dev-master",
        "da/oauth-client-bundle": "dev-master",
        "friendsofsymfony/user-bundle": "dev-master"
    },
```

Update your vendors:

``` bash
    composer update      # WIN
    composer.phar update # LINUX
```

### Step 2: Declare in the kernel

Declare the bundles in your kernel:

``` php
    // app/AppKernel.php

    $bundles = array(
        // ...
        new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
        new Da\OAuthClientBundle\DaOAuthClientBundle(),
        //new FOS\UserBundle\FOSUserBundle(), // ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
    );
```

### Step 3: Set the config

Here is the minimal config you will need to use the bundle:

``` yaml
    # app/config/config.yml

    # HWIOAuth Configuration
    hwi_oauth:
        firewall_name: secured_area
        resource_owners:
        connect:
            account_connector: da_oauth_client.user_provider

    # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
    # FOSUser Configuration
    #fos_user:
    #    db_driver: orm
    #    firewall_name: secured_area
    #    user_class: Da\OAuthClientBundle\Entity\User
```

### Step 4: Import the routing

You have to import some routes in order to run the bundle:

``` yaml
    # app/config/routing.yml

    # HWIOAuth Routes
    hwi_oauth_redirect:
        resource: "@HWIOAuthBundle/Resources/config/routing/redirect.xml"
        prefix:   /connect

    hwi_oauth_connect:
        resource: "@HWIOAuthBundle/Resources/config/routing/connect.xml"
        prefix:   /connect

    # DaOAuthClient Routes
    da_oauth_client:
        resource: "@DaOAuthClientBundle/Controller/"
        type:     annotation
        prefix:   /

    # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
    # FOSUser Routes
    #fos_user_security:
    #    resource: "@FOSUserBundle/Resources/config/routing/security.xml"

    #fos_user_profile:
    #    resource: "@FOSUserBundle/Resources/config/routing/profile.xml"
    #    prefix: /profile

    #fos_user_register:
    #    resource: "@FOSUserBundle/Resources/config/routing/registration.xml"
    #    prefix: /register

    #fos_user_resetting:
    #    resource: "@FOSUserBundle/Resources/config/routing/resetting.xml"
    #    prefix: /resetting

    #fos_user_change_password:
    #    resource: "@FOSUserBundle/Resources/config/routing/change_password.xml"
    #    prefix: /profile
```

Build your own "HWI Resource Owner"
-----------------------------------

You would like to build your own resource owner to communicate with your oauth server.

### Step 1: Define your resource owner class

``` php
namespace My\OwnBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GenericOAuth2ResourceOwner;

class MyResourceOwner extends GenericOAuth2ResourceOwner
{
    /**
     * {@inheritDoc}
     */
    protected $paths = array(
        'identifier'     => 'username',
        'nickname'       => 'username',
        'realname'       => 'username',
        'email'          => 'email',
        'raw'            => 'raw'
    );

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'authorization_url'   => 'https://my-oauth-server-domain/oauth/v2/auth',
            'access_token_url'    => 'https://my-oauth-server-domain/oauth/v2/token',
            'revoke_token_url'    => 'https://my-oauth-server-domain/oauth/v2/revoke',
            'disconnection_url'   => 'https://my-oauth-server-domain/oauth/v2/disconnect',
            'infos_url'           => 'https://my-oauth-server-domain/api/infos',

            'user_response_class' => '\Tms\Bundle\SsoClientBundle\OAuth\Response\UserResponse',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function revokeToken($token)
    {
        $parameters = array(
            'client_id'     => $this->getOption('client_id'),
            'client_secret' => $this->getOption('client_secret'),
        );

        /* @var $response \Buzz\Message\Response */
        $response = $this->httpRequest($this->normalizeUrl($this->getOption('revoke_token_url'), array('token' => $token)), $parameters, array(), 'DELETE');

        return 200 === $response->getStatusCode();
    }

    /**
     * Returns the provider's disconnection url
     *
     * @param string $redirectUri     The uri to redirect the client back to
     * @param array  $extraParameters An array of parameters to add to the url
     *
     * @return string The disconnection url
     */
    public function getDisconnectionUrl($redirectUri, array $extraParameters = array())
    {
        $parameters = array_merge(array(
            'client_id'     => $this->options['client_id'],
            'redirect_uri'  => $redirectUri
        ), $extraParameters);

        return $this->normalizeUrl($this->options['disconnection_url'], $parameters);
    }
}
```

### Step 2: Set the config

``` yaml
    # app/config/config.yml

    # ...

    # DaOAuthClient Configuration
    da_oauth_client:
        resource_owners:
            my:
                type:              my
                client_id:         adl1fhgf135fsd... # The client id given by the oauth server
                client_secret:     bdl4fghf28fsd6... # The client secret given by the oauth server
                authorization_url: 'https://my-oauth-server-domain/oauth/v2/auth'
                access_token_url:  'https://my-oauth-server-domain/oauth/v2/token'
                revoke_token_url:  'https://my-oauth-server-domain/oauth/v2/revoke'     # [OPTIONAL]
                disconnection_url: 'https://my-oauth-server-domain/oauth/v2/disconnect' # [OPTIONAL]
                infos_url:         'https://my-oauth-server-domain/api/infos'
        #fosub:  # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
        #    username_iterations: 5
        #    properties:
        #        my: username
```

### Step 3: Import the routing

``` yaml
    # app/config/routing.yml

    # ...

    # Login Routes
    my_login:
        pattern: /login/check-my
```

### Step 4: Set the security

Here is the minimal configuration for the security you will need to use the oauth authentication with your resource owner:

``` yaml
    # app/config/security.yml

    security:
        encoders:
            Symfony\Component\Security\Core\User\User: plaintext

        providers:
            da_oauth_client:
                id: da_oauth_client.user_provider.memory
            #fos_userbundle: # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
            #    id: fos_user.user_manager

        firewalls:
            dev:
                pattern:  ^/(_(profiler|wdt)|css|images|js)/
                security: false

            login:
                pattern:    ^/login$
                anonymous:  ~

            connect:
                pattern:    ^/connect
                anonymous:  ~

            secured_area:
                pattern: ^/   # Change this pattern if you do not want to use the SSO for all your routes.
                oauth:
                    resource_owners:
                        my: "/login/check-my"
                    login_path:   "/login"
                    failure_path: "/login"
                    oauth_user_provider:
                        service: da_oauth_client.user_provider.memory
                    #oauth_user_provider: # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
                    #    service: da_oauth_client.user_provider.fosub
                logout:
                    # BUG: https://github.com/sensiolabs/SensioDistributionBundle/commit/2a518e7c957b66c9478730ca95f67e16ccdc982b
                    invalidate_session: false

        access_control:
            - { path: ^/secured/freespace, role: IS_AUTHENTICATED_ANONYMOUSLY } # An insecured path
            - { path: ^/secured, role: IS_AUTHENTICATED_FULLY }                 # A secured path
            - { path: ^/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/connect, role: IS_AUTHENTICATED_ANONYMOUSLY }
```

Other Considerations
--------------------

* You must have set a database to store a local user which is created at the first authentication with the oauth server if you use the FOSUserBundle to persist the users.

Documentation
-------------

Read the [documentation](Resources/doc/index.md) for more informations on the bundle.