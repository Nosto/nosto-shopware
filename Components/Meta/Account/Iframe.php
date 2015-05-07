<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe implements NostoAccountMetaDataIframeInterface {
	/**
	 * @var string the admin user first name.
	 */
	protected $_first_name;

	/**
	 * @var string the admin user last name.
	 */
	protected $_last_name;

	/**
	 * @var string the admin user email address.
	 */
	protected $_email;

	/**
	 * @var string the language ISO (ISO 639-1) code for oauth server locale.
	 */
	protected $_language_iso_code;

	/**
	 * @var string the language ISO (ISO 639-1) for the store view scope.
	 */
	protected $_language_iso_code_shop;

	/**
	 * @var string unique ID that identifies the Shopware installation.
	 */
	protected $_unique_id;

	/**
	 * @var string preview url for the product page in the active store scope.
	 */
	protected $_preview_url_product;

	/**
	 * @var string preview url for the category page in the active store scope.
	 */
	protected $_preview_url_category;

	/**
	 * @var string preview url for the search page in the active store scope.
	 */
	protected $_preview_url_search;

	/**
	 * @var string preview url for the cart page in the active store scope.
	 */
	protected $_preview_url_cart;

	/**
	 * @var string preview url for the front page in the active store scope.
	 */
	protected $_preview_url_front;

	/**
	 * @var string the name of the store Nosto is installed in or about to be installed.
	 */
	protected $_shop_name;

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop) {
        $helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Url();
		$user = Shopware()->Auth()->getIdentity();
        list($first_name, $last_name) = explode(' ', $user->name);

		$this->_first_name = $first_name;
		$this->_last_name = $last_name;
		$this->_email = $user->email;
		$this->_language_iso_code = strtolower(substr($user->locale->getLocale(), 0, 2));
		$this->_language_iso_code_shop = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->_unique_id = Shopware()->Plugins()->Frontend()->NostoTagging()->getUniqueId();
		$this->_preview_url_product = $helper->getProductPagePreviewUrl($shop);
		$this->_preview_url_category = $helper->getCategoryPagePreviewUrl($shop);
		$this->_preview_url_search = $helper->getSearchPagePreviewUrl($shop);
		$this->_preview_url_cart = $helper->getCartPagePreviewUrl($shop);
		$this->_preview_url_front = $helper->getFrontPagePreviewUrl($shop);
		// todo: get name from "basic information"
		$this->_shop_name = Shopware()->App() . ' - ' . $shop->getName();
	}

	/**
	 * The name of the platform the iframe is used on.
	 * A list of valid platform names is issued by Nosto.
	 *
	 * @return string the platform name.
	 */
	public function getPlatform() {
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * The first name of the user who is loading the config iframe.
	 *
	 * @return string the first name.
	 */
	public function getFirstName() {
		return $this->_first_name;
	}

	/**
	 * The last name of the user who is loading the config iframe.
	 *
	 * @return string the last name.
	 */
	public function getLastName() {
		return $this->_last_name;
	}

	/**
	 * The email address of the user who is loading the config iframe.
	 *
	 * @return string the email address.
	 */
	public function getEmail() {
		return $this->_email;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the user who is loading the config iframe.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageIsoCode() {
		return $this->_language_iso_code;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the shop the account belongs to.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageIsoCodeShop() {
		return $this->_language_iso_code_shop;
	}

	/**
	 * Unique identifier for the e-commerce installation.
	 * This identifier is used to link accounts together that are created on the same installation.
	 *
	 * @return string the identifier.
	 */
	public function getUniqueId() {
		return $this->_unique_id;
	}

	/**
	 * The version number of the platform the e-commerce installation is running on.
	 *
	 * @return string the platform version.
	 */
	public function getVersionPlatform() {
		return \Shopware::VERSION;
	}

	/**
	 * The version number of the Nosto module/extension running on the e-commerce installation.
	 *
	 * @return string the module version.
	 */
	public function getVersionModule() {
		return Shopware()->Plugins()->Frontend()->NostoTagging()->getVersion();
	}

	/**
	 * An absolute URL for any product page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/products/product123?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlProduct() {
		return $this->_preview_url_product;
	}

	/**
	 * An absolute URL for any category page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/products/category123?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlCategory() {
		return $this->_preview_url_category;
	}

	/**
	 * An absolute URL for the search page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/search?query=red?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlSearch() {
		return $this->_preview_url_search;
	}

	/**
	 * An absolute URL for the cart page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/cart?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlCart() {
		return $this->_preview_url_cart;
	}

	/**
	 * An absolute URL for the front page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlFront() {
		return $this->_preview_url_front;
	}

	/**
	 * Returns the name of the shop context where Nosto is installed or about to be installed in.
	 *
	 * @return string the name.
	 */
	public function getShopName() {
		return $this->_shop_name;
	}
}
