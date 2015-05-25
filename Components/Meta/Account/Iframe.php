<?php

/**
 * Meta-data class for information included in the plugin configuration iframe.
 *
 * Implements NostoAccountMetaDataIframeInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe implements NostoAccountMetaDataIframeInterface
{
	/**
	 * @var string the admin user first name.
	 */
	protected $_firstName;

	/**
	 * @var string the admin user last name.
	 */
	protected $_lastName;

	/**
	 * @var string the admin user email address.
	 */
	protected $_email;

	/**
	 * @var string the language ISO (ISO 639-1) code for oauth server locale.
	 */
	protected $_languageIsoCode = 'en';

	/**
	 * @var string the language ISO (ISO 639-1) for the store view scope.
	 */
	protected $_languageIsoCodeShop = 'en';

	/**
	 * @var string unique ID that identifies the Shopware installation.
	 */
	protected $_uniqueId;

	/**
	 * @var string preview url for the product page in the active store scope.
	 */
	protected $_previewUrlProduct;

	/**
	 * @var string preview url for the category page in the active store scope.
	 */
	protected $_previewUrlCategory;

	/**
	 * @var string preview url for the search page in the active store scope.
	 */
	protected $_previewUrlSearch;

	/**
	 * @var string preview url for the cart page in the active store scope.
	 */
	protected $_previewUrlCart;

	/**
	 * @var string preview url for the front page in the active store scope.
	 */
	protected $_previewUrlFront;

	/**
	 * @var string the name of the store Nosto is installed in or about to be installed.
	 */
	protected $_shopName;

	/**
	 * Loads the iframe data from the shop model.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @param \Shopware\Models\Shop\Locale $locale the locale or null.
	 * @param stdClass|null $identity the user identity.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null, $identity = null)
	{
		if (is_null($locale)) {
			$locale = $shop->getLocale();
		}
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Url();
		/** @var Shopware_Plugins_Frontend_NostoTagging_Bootstrap $plugin */
		$plugin = Shopware()->Plugins()->Frontend()->NostoTagging();

		if (!is_null($identity)) {
			list($firstName, $lastName) = explode(' ', $identity->name);
			$this->_firstName = $firstName;
			$this->_lastName = $lastName;
			$this->_email = $identity->email;
		}
		$this->_languageIsoCode = strtolower(substr($locale->getLocale(), 0, 2));
		$this->_languageIsoCodeShop = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->_uniqueId = $plugin->getUniqueId();
		$this->_previewUrlProduct = $helper->getProductPagePreviewUrl($shop);
		$this->_previewUrlCategory = $helper->getCategoryPagePreviewUrl($shop);
		$this->_previewUrlSearch = $helper->getSearchPagePreviewUrl($shop);
		$this->_previewUrlCart = $helper->getCartPagePreviewUrl($shop);
		$this->_previewUrlFront = $helper->getFrontPagePreviewUrl($shop);
		$this->_shopName = Shopware()->App().' - '.$shop->getName();
	}

	/**
	 * The name of the platform the iframe is used on.
	 * A list of valid platform names is issued by Nosto.
	 *
	 * @return string the platform name.
	 */
	public function getPlatform()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * The first name of the user who is loading the config iframe.
	 *
	 * @return string the first name.
	 */
	public function getFirstName()
	{
		return $this->_firstName;
	}

	/**
	 * The last name of the user who is loading the config iframe.
	 *
	 * @return string the last name.
	 */
	public function getLastName()
	{
		return $this->_lastName;
	}

	/**
	 * The email address of the user who is loading the config iframe.
	 *
	 * @return string the email address.
	 */
	public function getEmail()
	{
		return $this->_email;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the user who is loading the config iframe.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageIsoCode()
	{
		return $this->_languageIsoCode;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the shop the account belongs to.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageIsoCodeShop()
	{
		return $this->_languageIsoCodeShop;
	}

	/**
	 * Unique identifier for the e-commerce installation.
	 * This identifier is used to link accounts together that are created on the same installation.
	 *
	 * @return string the identifier.
	 */
	public function getUniqueId()
	{
		return $this->_uniqueId;
	}

	/**
	 * The version number of the platform the e-commerce installation is running on.
	 *
	 * @return string the platform version.
	 */
	public function getVersionPlatform()
	{
		return \Shopware::VERSION;
	}

	/**
	 * The version number of the Nosto module/extension running on the e-commerce installation.
	 *
	 * @return string the module version.
	 */
	public function getVersionModule()
	{
		return Shopware()->Plugins()->Frontend()->NostoTagging()->getVersion();
	}

	/**
	 * An absolute URL for any product page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/products/product123?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlProduct()
	{
		return $this->_previewUrlProduct;
	}

	/**
	 * An absolute URL for any category page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/products/category123?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlCategory()
	{
		return $this->_previewUrlCategory;
	}

	/**
	 * An absolute URL for the search page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/search?query=red?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlSearch()
	{
		return $this->_previewUrlSearch;
	}

	/**
	 * An absolute URL for the cart page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com/cart?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlCart()
	{
		return $this->_previewUrlCart;
	}

	/**
	 * An absolute URL for the front page in the shop the account is linked to, with the nostodebug GET parameter
	 * enabled. e.g. http://myshop.com?nostodebug=true
	 * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
	 *
	 * @return string the url.
	 */
	public function getPreviewUrlFront()
	{
		return $this->_previewUrlFront;
	}

	/**
	 * Returns the name of the shop context where Nosto is installed or about to be installed in.
	 *
	 * @return string the name.
	 */
	public function getShopName()
	{
		return $this->_shopName;
	}
}
