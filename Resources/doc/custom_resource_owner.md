Create your own resource owner
==============================

It is possible to use several authspaces for a resource owner.

Step 1: Define your resource owner class
----------------------------------------

```php
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

Step 2: Set the config
----------------------

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
```

Step 3: Import the routing
--------------------------

```yaml
    # app/config/routing.yml

    # ...

    # Login Routes
    my_login:
        pattern: /login/check-my
```

Step 4: Set the security
------------------------

Here is the minimal configuration for the security you will need to use the oauth authentication with your resource owner:

```yaml
    # app/config/security.yml

    security:
        encoders:
            Symfony\Component\Security\Core\User\User: plaintext

        providers:
            da_oauth_client:
                id: da_oauth_client.user_provider.memory

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
                logout:
                    # BUG: https://github.com/sensiolabs/SensioDistributionBundle/commit/2a518e7c957b66c9478730ca95f67e16ccdc982b
                    invalidate_session: false

        access_control:
            - { path: ^/secured/freespace, role: IS_AUTHENTICATED_ANONYMOUSLY } # An insecured path
            - { path: ^/secured, role: IS_AUTHENTICATED_FULLY }                 # A secured path
            - { path: ^/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/connect, role: IS_AUTHENTICATED_ANONYMOUSLY }
```
