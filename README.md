Setting Bundle
========================

[![Latest Stable Version](https://poser.pugx.org/hengebytes/setting-bundle/v/stable.svg)](https://packagist.org/packages/hengebytes/setting-bundle)
[![Total Downloads](https://poser.pugx.org/hengebytes/setting-bundle/downloads.svg)](https://packagist.org/packages/hengebytes/setting-bundle)
[![License](https://poser.pugx.org/hengebytes/setting-bundle/license.svg)](https://packagist.org/packages/hengebytes/setting-bundle)

About bundle
---------------------------
This bundle provides a simple way to manage settings in your Symfony application as a key-value.
Sensitive data is encrypted. If you want to update the sensitive setting, you need to send the raw value again.
The bundle provides an API to manage settings.


Installation
============

Step 1: Download the Bundle
---------------------------

```bash
    composer update
```

#### Create the CRYPTO_KEY
generated as follows:
```php
echo sodium_bin2hex(sodium_crypto_secretbox_keygen());
```

Add the generated key to your .env file:
```
CRYPTO_KEY=your_generated_key
```

Ensure that you have the variable in docker compose file:
```
    environment:
      - CRYPTO_KEY=${CRYPTO_KEY}
```

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles:

```php
// config/bundles.php
return [
    // ...
    Hengebytes\SettingBundle\HBSettingBundle::class => ['all' => true],
];
}
```

Step 3: Config the Routing
--------------------------

Then, enable the routes by adding it to the route list
in the `app/config/routing.yml` file of your project: 

```yaml
# app/config/routing.yml

setting_routes:
    resource: "@HBSettingBundle/Resources/config/routing.yml"
    prefix:   /  # some admin path prefix
```

Step 4: Assets (Optional)
--------------------------

If you want to use admin UI you need to install assets:

```bash

    $ php bin/console assets:install
```

Step 5: API
--------------------------

Include the following in your `config/routes.yaml` file:
```yaml
setting_api:
    resource: "@HBSettingBundle/Resources/config/api_routing.yml"
    prefix: /api
```

The API is not secured by default. You should secure it by adding a firewall in your `config/packages/security.yaml` file:
```yaml
security:
    firewalls:
        setting_api:
            pattern: ^/api/settings
            stateless: true
            anonymous: false
            provider: app_user_provider
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator
```
### The following requests are available:

List of settings can be retrieved by the following request:

`GET /api/settings/list`

`POST /api/settings`

POST requests should have the following body
```json
{
    "name": "general/stuff/secret1",
    "value": "test",
    "is_sensitive": false
}
```
`DELETE /api/settings/list`

DELETE requests should have the following body:
```json
{
  "settings": ["setting/line1", "setting/line2", "setting/line3"]
}
```
`POST /api/settings/list`

POST requests should have the following body:
```json
{
  "settings": [
    {
      "name": "setting/line1",
      "value": "test",
      "is_sensitive": false
    },
    {
      "name": "setting/line2",
      "value": "test",
      "is_sensitive": false
    },
    {
      "name": "setting/line3",
      "value": "test",
      "is_sensitive": false
    }
  ]
}
```
