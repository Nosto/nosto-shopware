# Personalization for Shopware

Increase your conversion rate and average order value by delivering your
customers personalized product recommendations throughout their shopping
journey.

Nosto allows you to deliver every customer a personalized shopping experience
through recommendations based on their unique user behavior - increasing
conversion, average order value and customer retention as a result.

[http://nosto.com](http://nosto.com/)

## Getting started

### How it works

coming soon...

### Installing

The preferred way of installing the plugin is through the Shopware Community
Store. It can, however, also be installed as a local plugin package or directly
from the GitHub repository if needed.

#### Community (preferred)

coming soon...

#### Local

The plugin can also be installed as a "local" plugin by uploading the plugin
archive file manually in the Shopware Plugin Manager. The plugin archive file
can be obtained from the projects
[releases](https://github.com/Nosto/nosto-shopware-plugin/releases/) page on
GitHub.

#### Repository

For development purposes the plugin can be installed directly from the GitHub
repository by cloning the project into the
`/engine/Shopware/Plugins/Local/Frontend/NostoTagging` directory of the Shopware
installation. For the plugin to work, it's dependencies need to be installed.
For this we recommend using [composer](https://getcomposer.org/), which is a
dependency manager for PHP. By executing `composer install` in the plugins root
folder, the dependencies will be automatically fetched and installed in a
 `vendor` folder relative to the plugin root directory.

After this, the plugin can be activated in the Shopware Plugin Manager.

### Configuration

The plugin creates a new menu item during installation, that is located under
the `Settings` menu in the backend. Note that you may have to clear the cache
and reload the backend for the menu item to show up.

By clicking the menu item, a window will open showing a Nosto account
configuration per shop that is installed. You will need a Nosto account for each
shop.

Creating the account is as easy as clicking the install button on the page. Note
the email field above it. You will need to enter your own email to be able to
activate your account. After clicking install, the window will refresh and show
the account configuration.

You can also connect and existing Nosto account to a shop, by using the link
below the install button. This will take you to Nosto where you choose the
account to connect, and will then redirect you back where you will see the same
configuration screen as when having created a new account.

This concludes the needed configurations in shopware. Now you should be able to
view the default recommendations in your shops frontend by clicking the preview
button on the page.

You can read more about how to modify Nosto to suit your needs in the
[support center](https://support.nosto.com/), where you will find Shopware
related documentation and guides.

### Extending

coming soon...

## License

BSD 3-Clause (http://opensource.org/licenses/BSD-3-Clause)

## Dependencies

* Shopware Community Edition 4 and 5

## Changelog

### 0.3.0
* Bug fixes for older SW 4 versions

### 0.2.0
* Add "add-to-cart" feature to enable adding products to cart directly from the
recommendations

### 0.1.0
* Initial beta-release
