<?php

/**
 * Meta-data class for account information sent to Nosto during account create.
 *
 * Implements NostoAccountMetaDataInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account implements NostoAccountMetaDataInterface
{
	/**
	 * @var string the store name.
	 */
	protected $_title;

	/**
	 * @var string the account name.
	 */
	protected $_name;

	/**
	 * @var string the store front end url.
	 */
	protected $_frontPageUrl;

	/**
	 * @var string the store currency ISO (ISO 4217) code.
	 */
	protected $_currencyCode;

	/**
	 * @var string the store language ISO (ISO 639-1) code.
	 */
	protected $_languageCode;

	/**
	 * @var string the owner language ISO (ISO 639-1) code.
	 */
	protected $_ownerLanguageCode;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner the account owner meta model.
	 */
	protected $_owner;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing the billing meta model.
	 */
	protected $_billing;

	/**
	 * @var string the API token used to identify an account creation.
	 */
	protected $_signUpApiToken = 'kIqtTZOTRTNJ1zPZgjkI4Ft572sfLrqjD4XewXqYrdGrqsgnYbWqGXR3Evxqmii1';

	/**
	 * Loads the meta data for the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop view to load the data for.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop)
	{
		$this->_title = Shopware()->App().' - '.$shop->getName();
		$this->_name = substr(sha1(rand()), 0, 8);
		$this->_frontPageUrl = Shopware()->Front()->Router()->assemble(array('module' => 'frontend'));
		$this->_currencyCode = strtoupper($shop->getCurrency()->getCurrency());
		$this->_languageCode = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->_ownerLanguageCode = strtolower(substr(Shopware()->Auth()->getIdentity()->locale->getLocale(), 0, 2));

		$this->_owner = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner();
		$this->_owner->loadData($shop);

		$this->_billing = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing();
		$this->_billing->loadData($shop);
	}

	/**
	 * Sets the store title.
	 *
	 * @param string $title the store title.
	 */
	public function setTitle($title)
	{
		$this->_title = $title;
	}

	/**
	 * The shops name for which the account is to be created for.
	 *
	 * @return string the name.
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Sets the account name.
	 *
	 * @param string $name the account name.
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}

	/**
	 * The name of the account to create.
	 * This has to follow the pattern of
	 * "[platform name]-[8 character lowercase alpha numeric string]".
	 *
	 * @return string the account name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * The name of the platform the account is used on.
	 * A list of valid platform names is issued by Nosto.
	 *
	 * @return string the platform names.
	 */
	public function getPlatform()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * Sets the store front page url.
	 *
	 * @param string $url the front page url.
	 */
	public function setFrontPageUrl($url)
	{
		$this->_frontPageUrl = $url;
	}

	/**
	 * Absolute url to the front page of the shop for which the account is
	 * created for.
	 *
	 * @return string the url.
	 */
	public function getFrontPageUrl()
	{
		return $this->_frontPageUrl;
	}

	/**
	 * Sets the store currency ISO (ISO 4217) code.
	 *
	 * @param string $code the currency ISO code.
	 */
	public function setCurrencyCode($code)
	{
		$this->_currencyCode = $code;
	}

	/**
	 * The 3-letter ISO code (ISO 4217) for the currency used by the shop for
	 * which the account is created for.
	 *
	 * @return string the currency ISO code.
	 */
	public function getCurrencyCode()
	{
		return $this->_currencyCode;
	}

	/**
	 * Sets the store language ISO (ISO 639-1) code.
	 *
	 * @param string $languageCode the language ISO code.
	 */
	public function setLanguageCode($languageCode)
	{
		$this->_languageCode = $languageCode;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language used by the shop for
	 * which the account is created for.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageCode()
	{
		return $this->_languageCode;
	}

	/**
	 * Sets the owner language ISO (ISO 639-1) code.
	 *
	 * @param string $languageCode the language ISO code.
	 */
	public function setOwnerLanguageCode($languageCode)
	{
		$this->_ownerLanguageCode = $languageCode;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the account owner
	 * who is creating the account.
	 *
	 * @return string the language ISO code.
	 */
	public function getOwnerLanguageCode()
	{
		return $this->_ownerLanguageCode;
	}

	/**
	 * Meta data model for the account owner who is creating the account.
	 *
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner the meta data model.
	 */
	public function getOwner()
	{
		return $this->_owner;
	}

	/**
	 * Meta data model for the account billing details.
	 *
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing the meta data model.
	 */
	public function getBillingDetails()
	{
		return $this->_billing;
	}

	/**
	 * The API token used to identify an account creation.
	 * This token is platform specific and issued by Nosto.
	 *
	 * @return string the API token.
	 */
	public function getSignUpApiToken()
	{
		return $this->_signUpApiToken;
	}
}
