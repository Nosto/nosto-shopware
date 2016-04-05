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

The plugin automatically adds product recommendation elements to the shop when
installed. Basically, empty "div" placeholder elements. These elements will
appear on the home page, product pages, category pages, search result pages and
the shopping cart page. These elements are automatically populated with product
recommendations from your shop.

This is possible by mining data from the shop when the user visits the pages.
For example, when the user is browsing a product page, the product information
is asynchronously sent to Nosto, that in turn delivers product recommendations
based on that product to the shop and displays them to the user.

The more users that are visiting the site, and the more page views they create,
the better and more accurate the recommendations become.

In addition to the recommendation elements and the real time data gathering, the
plugin also includes some behind the scenes features for keeping the product
information up to date and keeping track of orders in the shop.

Every time a product is updated in the shop, e.g. the price is changed, the
information is sent to Nosto over an API. This will sync the data across all
the users visiting the shop that will see up to date recommendations.

All orders that are placed in the shop are also sent to Nosto. This is done to
keep track of the orders that were a direct result of the product
recommendations, i.e. when a user clicks a product in the recommendation,
adds it to the shopping cart and places the order.

Nosto also keeps track of the order statuses, i.e. when an order is changed to
"payed" or "canceled" the order is updated over an API.

All you need to take Nosto into use in your shop, is to install the plugin and
create a Nosto account for your shop. This is as easy as clicking a button, so
read on.

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
account to connect, and you will then be redirected back where you will see the
same configuration screen as when having created a new account.

This concludes the needed configurations in shopware. Now you should be able to
view the default recommendations in your shops frontend by clicking the preview
button on the page.

You can read more about how to modify Nosto to suit your needs in our
[support center](https://support.nosto.com/), where you will find Shopware
related documentation and guides.

### Extending

coming soon...

## License

BSD 3-Clause (http://opensource.org/licenses/BSD-3-Clause)

## Dependencies

* Shopware Community Edition 4 and 5

## Changelog

### 1.1.0
* Support using recommendations in Shopping Worlds
* Add tagging to 404 pages
* Automatic price per unit tagging (tag2) 
* Fir for handling empty payment provider

### 1.0.4
* Fix the order and product export controller to respect the shop parameters
* Add support for MediaService in Shopware 5.1
* Add functionality for overriding the product data
* Add the shop-selection parameter to the signup and OAuth redirect URLs
* Remove the validation for the products and orders during export
* Add recommendation slots to the order confirmation page
* Use the upsert endpoint for product creation and updation
* Fix the offsets used when exporting orders and products
* Fix the email address use when creating a new account
* Fix a bug where the email address in account opening is omitted

### 1.0.3
* Remove check for existing image file in the file system, as it makes it hard
to debug image url issues

### 1.0.2
* Check that a price model is found before using it in the product model
* Fix product image url protocol to be based on the current shop settings

### 1.0.1
* Fix fatal error in product when image has no media

### 1.0.0
* First public release

### 0.6.0
* Add order status and payment provider to order tagging
* Fix window.postMessage event origin validation to work with new sub-domains

### 0.5.0
* Change the Nosto account configuration iframe to use merchant specific
sub-domains in the urls to allow editing multiple accounts simultaneously
* Fix cart/order tagging to use correct product id and name for line items, so
that the different product variations can be recognized in emails

### 0.4.1
* Fix product image tagging to check if image exists

### 0.4.0
* Add unique indexes to db tables
* Add Nosto meta tags to the frontend pages
* Fix product availability value to take product variations into consideration
* Fix account connection OAuth flow to open the Nosto configuration after being
redirected back from Nosto

### 0.3.0
* Bug fixes for older SW 4 versions

### 0.2.0
* Add "add-to-cart" feature to enable adding products to cart directly from the
recommendations

### 0.1.0
* Initial beta-release
