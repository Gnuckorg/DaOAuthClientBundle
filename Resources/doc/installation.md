Install and use the bundle
==========================

Step 1: Add in composer
-----------------------

Add the bundle and its dependencies in the composer.json file:

```js
    // composer.json

    "require": {
        // ...
        "hwi/oauth-bundle": "0.3.*@dev",
        "da/auth-common-bundle": "dev-master",
        "da/oauth-client-bundle": "dev-master"
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
        new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
        new Da\OAuthClientBundle\DaOAuthClientBundle(),
    );
```

Step 3: Set the config
----------------------

Here is the minimal config you will need to use the bundle:

```yaml
    # app/config/config.yml

    # HWIOAuth Configuration
    hwi_oauth:
        firewall_name: secured_area
        resource_owners:
        connect:
            account_connector: da_oauth_client.user_provider
```

Step 4: Import the routing
--------------------------

You have to import some routes in order to run the bundle:

```yaml
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
```