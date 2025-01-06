Setting Bundle
========================

About bundle
---------------------------
This bundle provides a simple way to manage settings in your Symfony application.


Installation
============

Step 1: Download the Bundle
---------------------------

Edit your project's `composer.json` file to require the bundle:

```
    "require" : {
        ...
        "hengebytes/setting-bundle" : "1.0.*",
    }, 
    "repositories" : [{ 
        "type" : "git", 
        "url" : "git@github.com:hengebytes/setting-bundle.git" 
    }],
    ...
```

Now, run:

```bash

    $ composer update
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles:

```php
// config/bundles.php
return [
    // ...
    Hengebytes\SettingBundle\SettingBundle::class => ['all' => true],
];
}
```


Step 3: Config the Routing
--------------------------

Then, enable the routs by adding it to the rout list
in the `app/config/routing.yml` file of your project: 
```
And You will have: 

Settings        -  /settings
```
```yaml
# app/config/routing.yml

Settings_routs:
    resource: "@SettingBundle/Resources/config/routing.yml"
    prefix:   /  # some admin path
```

Step 4: Assets (Optional)
--------------------------

If you want to use admin UI you need to install assets:

```bash

    $ php bin/console assets:install
```
