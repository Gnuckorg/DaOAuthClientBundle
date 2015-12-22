Persist users to a local database
=================================

If you want to persist the users to a local database, you can use [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle).

Step 1: Add in composer
-----------------------

Add the bundle in the composer.json file:

```js
    // composer.json

    "require": {
        // ...
        "friendsofsymfony/user-bundle": "dev-master"
    },
```

Update your vendors:

```bash
    composer update
```

Step 2: Declare in the kernel
-----------------------------

Declare the bundles in your kernel:

```php
    // app/AppKernel.php

    $bundles = array(
        // ...
        new FOS\UserBundle\FOSUserBundle(),
    );
```

Step 3: Set the config
----------------------

Here is an example of config you will need to use the bundle:

```yaml
    # app/config/config.yml

    # FOSUser Configuration
    fos_user:
        db_driver: orm
        firewall_name: secured_area
        user_class: Da\OAuthClientBundle\Entity\User
```

Step 4: Import the routing
--------------------------

You have to import some routes in order to run the bundle:

```yaml
    # app/config/routing.yml

    # FOSUser Routes
    fos_user_security:
        resource: "@FOSUserBundle/Resources/config/routing/security.xml"

    fos_user_profile:
        resource: "@FOSUserBundle/Resources/config/routing/profile.xml"
        prefix: /profile

    fos_user_register:
        resource: "@FOSUserBundle/Resources/config/routing/registration.xml"
        prefix: /register

    fos_user_resetting:
        resource: "@FOSUserBundle/Resources/config/routing/resetting.xml"
        prefix: /resetting

    fos_user_change_password:
        resource: "@FOSUserBundle/Resources/config/routing/change_password.xml"
        prefix: /profile
```

Step 5: Impact custom resource owner configuration
--------------------------------------------------

If you use your own resource owner, you may need to change some configuration:

```yaml
    # app/config/config.yml

    # ...

    # DaOAuthClient Configuration
    da_oauth_client:
        default_resource_owner: my
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
        fosub:
            username_iterations: 5
            properties:
                my: username
```

Here is the impacted security:

```yaml
    # app/config/security.yml

    security:
        encoders:
            Symfony\Component\Security\Core\User\User: plaintext

        providers:
            fos_userbundle:
                id: fos_user.user_manager

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
                    oauth_user_provider: # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
                        service: da_oauth_client.user_provider.fosub
                logout:
                    # BUG: https://github.com/sensiolabs/SensioDistributionBundle/commit/2a518e7c957b66c9478730ca95f67e16ccdc982b
                    invalidate_session: false

        access_control:
            - { path: ^/secured/freespace, role: IS_AUTHENTICATED_ANONYMOUSLY } # An insecured path
            - { path: ^/secured, role: IS_AUTHENTICATED_FULLY }                 # A secured path
            - { path: ^/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/connect, role: IS_AUTHENTICATED_ANONYMOUSLY }
```