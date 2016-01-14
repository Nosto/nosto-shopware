# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
### Added
- Multi-currency + exchange rate support
- Account synchronisation support
- Option to use direct include of Nosto script
- Events after data models are loaded to provide data overrides

## [1.0.3]
### Fixed
- Remove check for existing image file in the file system, as it makes it hard
to debug image url issues

## [1.0.2]
### Fixed
- Check that a price model is found before using it in the product model
- Product image url protocol to be based on the current shop settings

## [1.0.1]
### Fixed
- Fatal error in product when image has no media

## [1.0.0]
### Added
- First public release

## [0.6.0]
### Added
- Order status and payment provider to order tagging

### Fixed
- window.postMessage event origin validation to work with new sub-domains

## [0.5.0]
### Changed
- The Nosto account configuration iframe to use merchant specific sub-domains in
the urls to allow editing multiple accounts simultaneously

### Fixed
- Cart/order tagging to use correct product id and name for line items, so that
the different product variations can be recognized in emails

## [0.4.1]
### Fixed
- Product image tagging to check if image exists

## [0.4.0]
### Added
- Unique indexes to db tables
- Nosto meta tags to the frontend pages

### Fixed
- Product availability value to take product variations into consideration
- Account connection OAuth flow to open the Nosto configuration after being
redirected back from Nosto

## [0.3.0]
### Fixed
- Bug fixes for older SW 4 versions

## [0.2.0]
### Added
- "add-to-cart" feature to enable adding products to cart directly from the
recommendations

## 0.1.0
### Added
- Initial beta-release


[unreleased]: https://github.com/nosto/nosto-shopware-plugin/compare/1.0.3...develop
[1.0.3]: https://github.com/nosto/nosto-shopware-plugin/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/nosto/nosto-shopware-plugin/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/nosto/nosto-shopware-plugin/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.6.0...1.0.0
[0.6.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.4.1...0.5.0
[0.4.1]: https://github.com/nosto/nosto-shopware-plugin/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/nosto/nosto-shopware-plugin/compare/0.1.0...0.2.0
