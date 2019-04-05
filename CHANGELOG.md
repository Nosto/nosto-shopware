# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 2.3.3
- Encode HTML characters automatically

## 2.3.2
- Fix tax calculation for Nosto product price to factor in the tax rules instead of only the default tax 

## 2.3.1
- Bump Nosto SDK version to fix the double encoded Oauth redirect URL 

## 2.3.0
- Respect configured tax rules in product tagging
- Add index to table s_nostotagging_customer for column nosto_id
- Bump Nosto SDK version to support HTTP 2

## 2.2.0 
- Add support for multi-currency

## 2.1.5
- Prevent crash upon installation when database table already exists

## 2.1.4
- Fix the product id and sku id in cart tagging

## 2.1.3
- Add feature flag to enable/disable tagging product properties to custom fields

## 2.1.2
- Fix SKU variations for single products
- Add variant configuration group options into custom attributes
- Fix issue when updating the plugin could not create database column for cart restore hash
- fix missing category tagging in category listing page

## 2.1.1
- Fix backwards compatibility with PHP 5.4

## 2.1.0
- Add restore cart link to cart tagging
- Add inventory level and supplier cost to product API calls
- Renders tagging programmatically
- AddSkuToCart function to template
- Add custom field tagging for product and SKU 
- Add product streams support 

## 2.0.4
- Fix marketing permission tagging

## 2.0.3
- Fix order confirmation handling when using Shopware API or cli

## 2.0.2
- Fix PHP backwards compatibility

## 2.0.1
- Fix builder deleting composer autoloader

## 2.0.0
- Bump SDK to 3.3.1
- Fix shopware platform version detection
- Handle opt-in for customer and buyer

## 1.2.6
- Add support for Shopware 5.4

## 1.2.5
- Add support for Shopware 5.3

## 1.2.4
- Make order status handling compatible with Shopware < 5.1

## 1.2.3
- Fix a bug that cause php fatal error on shopware 4

## 1.2.2
- Fix a bug to prevent two shopware sub shops connect to same nosto account

## 1.2.1
- Fix a bug that cause php fatal error in certain sub shop setup

## 1.2.0
- Add support for multi-store, customer groups and price groups
- Add ratings, reviews, inventory level, supplier cost, alternative images 
- Fix the customer reference tag class
- Clean up the extension code, bump SDK, add Phan, PHPCS, PHPCBF, PHPMD

## 1.1.9
- Refactor utility methods to be static
- Add visitor tagging hash
- Add "js stub" for Nosto javascript

## 1.1.8
- Fix issue in update method
- Clear shopware cache after Nosto plug-in is installed or updated

## 1.1.7
- Remove date_published field from product tagging
- Add page type tagging
- Add customer reference tagging
- Use direct include for Nosto javascript
- Fix add to cart javascript function
- Handle some deprecated calls that will be removed in Shopware 5.3

## 1.1.6
- Add support for using multiple currencies across different stores

## 1.1.5
- Add support for the new Nosto UI

## 1.1.4
- Fix the product duplicating 

## 1.1.3
- Fix failing installation with Shopware 5.2
- Add safeguards for method calls to non-objects

## 1.1.2
- Add extendability for line items, cart, order, customer, category and buyer
- Fix product update listener when only product details are updated
- Add support for account details
- Set user agent for API calls

## 1.1.1
- Fix product delete bug
- Introduce packaging with phing (development only)

## 1.1.0
- Support using recommendations in Shopping Worlds
- Add tagging to 404 pages
- Automatic price per unit tagging (tag2)
- Fix for handling empty payment provider

## 1.0.4
- Fix the order and product export controller to respect the shop parameters
- Add support for MediaService in Shopware 5.1
- Add functionality for overriding the product data
- Add the shop-selection parameter to the signup and OAuth redirect URLs
- Remove the validation for the products and orders during export
- Add recommendation slots to the order confirmation page
- Use the upsert endpoint for product creation and updation
- Fix the offsets used when exporting orders and products
- Fix the email address use when creating a new account
- Fix a bug where the email address in account opening is omitted

## 1.0.3
- Remove check for existing image file in the file system, as it makes it hard
to debug image url issues

## 1.0.2
- Check that a price model is found before using it in the product model
- Fix product image url protocol to be based on the current shop settings

## 1.0.1
* Fix fatal error in product when image has no media

## 1.0.0
### Added
- First public release

## 0.6.0
### Added
- Order status and payment provider to order tagging

### Fixed
- window.postMessage event origin validation to work with new sub-domains

## 0.5.0
### Changed
- The Nosto account configuration iFrame to use merchant specific sub-domains in
the urls to allow editing multiple accounts simultaneously

### Fixed
- Cart/order tagging to use correct product id and name for line items, so that
the different product variations can be recognized in emails

## 0.4.1
### Fixed
- Product image tagging to check if image exists

## 0.4.0
### Added
- Unique indexes to db tables
- Nosto meta tags to the frontend pages

### Fixed
- Product availability value to take product variations into consideration
- Account connection OAuth flow to open the Nosto configuration after being
redirected back from Nosto

## 0.3.0
### Fixed
- Bug fixes for older SW 4 versions

## 0.2.0
### Added
- "add-to-cart" feature to enable adding products to cart directly from the
recommendations

## 0.1.0
### Added
- Initial beta-release
