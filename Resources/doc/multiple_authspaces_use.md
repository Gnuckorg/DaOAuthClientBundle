DaOAuthClientBundle - Use a Resource Owner on Multiple Authspaces
=================================================================

Here is the steps to use a same resource owner on many authspaces in the same application.

Step 1: Define your resource owner class
----------------------------------------

Take a look at the main [README](https://github.com/Gnuckorg/DaOAuthClientBundle/blob/master/README.md).

Step 2: Set the config
----------------------

``` yaml
    # app/config/config.yml

    # ...

    # DaOAuthClient Configuration
    da_oauth_client:
        resource_owners:
            my_authspace1:
                type:              my
                client_id:         adl1fhgf135fsd...
                client_secret:     bdl4fghf28fsd6...
                authorization_url: 'https://my-oauth-server-domain/oauth/v2/auth'
                access_token_url:  'https://my-oauth-server-domain/oauth/v2/token'
                revoke_token_url:  'https://my-oauth-server-domain/oauth/v2/revoke'
                disconnection_url: 'https://my-oauth-server-domain/oauth/v2/disconnect'
                infos_url:         'https://my-oauth-server-domain/api/infos'
            my_authspace2:
                type:              my
                client_id:         cdl1fhga135fse...
                client_secret:     edl4fghj28fsd7...
                authorization_url: 'https://my-oauth-server-domain/oauth/v2/auth'
                access_token_url:  'https://my-oauth-server-domain/oauth/v2/token'
                revoke_token_url:  'https://my-oauth-server-domain/oauth/v2/revoke'
                disconnection_url: 'https://my-oauth-server-domain/oauth/v2/disconnect'
                infos_url:         'https://my-oauth-server-domain/api/infos'
        #fosub:  # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
        #    username_iterations: 5
        #    properties:
        #        my: username
```

Step 3: Import the routing
--------------------------

``` yaml
    # app/config/routing.yml

    # ...

    # Login Routes
    my_authspace1_login:
        pattern: /authspace1/login/check-my

    my_authspace2_login:
        pattern: /authspace2/login/check-my
```

Step 4: Set the security
------------------------

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

            secured_area_authspace1:
                pattern: ^/authspace1
                oauth:
                    resource_owners:
                        my: "/authspace1/login/check-my"
                    login_path:   "/login/authspace1"
                    failure_path: "/login/authspace1"
                    oauth_user_provider:
                        service: da_oauth_client.user_provider.memory
                    #oauth_user_provider: # ONLY IF YOU WANT TO PERSIST THE USERS WITH FOSUB
                    #    service: da_oauth_client.user_provider.fosub
                logout:
                    # BUG: https://github.com/sensiolabs/SensioDistributionBundle/commit/2a518e7c957b66c9478730ca95f67e16ccdc982b
                    invalidate_session: false

            secured_area_authspace2:
                pattern: ^/authspace2
                oauth:
                    resource_owners:
                        my: "/authspace2/login/check-my"
                    login_path:   "/login/authspace2"
                    failure_path: "/login/authspace2"
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