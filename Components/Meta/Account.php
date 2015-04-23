<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account implements NostoAccountMetaDataInterface {
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
	protected $_front_page_url;

	/**
	 * @var string the store currency ISO (ISO 4217) code.
	 */
	protected $_currency_code;

	/**
	 * @var string the store language ISO (ISO 639-1) code.
	 */
	protected $_language_code;

	/**
	 * @var string the owner language ISO (ISO 639-1) code.
	 */
	protected $_owner_language_code;

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
	protected $_sign_up_api_token = 'YBDKYwSqTCzSsU8Bwbg4im2pkHMcgTy9cCX7vevjJwON1UISJIwXOLMM0a8nZY7h'; // todo: update once one exists for shopware (this is magento)

	/**
	 * Loads the meta data for the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop view to load the data for.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop) {
		// todo: get name from "basic information"
		$this->_title = Shopware()->App() . ' - ' . $shop->getName();
		$this->_name = substr(sha1(rand()), 0, 8);
		$this->_front_page_url = 'http://localhost:1337/shopware/4.3.4/'; // todo: get shop url from somewhere
		$this->_currency_code = strtoupper($shop->getCurrency()->getCurrency());
		$this->_language_code = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->_owner_language_code = strtolower(substr(Shopware()->Auth()->getIdentity()->locale->getLocale(), 0, 2));

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
	public function setTitle($title) {
		$this->_title = $title;
	}

	/**
	 * The shops name for which the account is to be created for.
	 *
	 * @return string the name.
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * Sets the account name.
	 *
	 * @param string $name the account name.
	 */
	public function setName($name) {
		$this->_name = $name;
	}

	/**
	 * The name of the account to create.
	 * This has to follow the pattern of
	 * "[platform name]-[8 character lowercase alpha numeric string]".
	 *
	 * @return string the account name.
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * The name of the platform the account is used on.
	 * A list of valid platform names is issued by Nosto.
	 *
	 * @return string the platform names.
	 */
	public function getPlatform() {
		return 'magento'; // todo: update to 'shopware' once it's available
	}

	/**
	 * Sets the store front page url.
	 *
	 * @param string $url the front page url.
	 */
	public function setFrontPageUrl($url) {
		$this->_front_page_url = $url;
	}

	/**
	 * Absolute url to the front page of the shop for which the account is
	 * created for.
	 *
	 * @return string the url.
	 */
	public function getFrontPageUrl() {
		return $this->_front_page_url;
	}

	/**
	 * Sets the store currency ISO (ISO 4217) code.
	 *
	 * @param string $code the currency ISO code.
	 */
	public function setCurrencyCode($code) {
		$this->_currency_code = $code;
	}

	/**
	 * The 3-letter ISO code (ISO 4217) for the currency used by the shop for
	 * which the account is created for.
	 *
	 * @return string the currency ISO code.
	 */
	public function getCurrencyCode() {
		return $this->_currency_code;
	}

	/**
	 * Sets the store language ISO (ISO 639-1) code.
	 *
	 * @param string $languageCode the language ISO code.
	 */
	public function setLanguageCode($languageCode) {
		$this->_language_code = $languageCode;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language used by the shop for
	 * which the account is created for.
	 *
	 * @return string the language ISO code.
	 */
	public function getLanguageCode() {
		return $this->_language_code;
	}

	/**
	 * Sets the owner language ISO (ISO 639-1) code.
	 *
	 * @param string $languageCode the language ISO code.
	 */
	public function setOwnerLanguageCode($languageCode) {
		$this->_owner_language_code = $languageCode;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language of the account owner
	 * who is creating the account.
	 *
	 * @return string the language ISO code.
	 */
	public function getOwnerLanguageCode() {
		return $this->_owner_language_code;
	}

	/**
	 * Meta data model for the account owner who is creating the account.
	 *
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner the meta data model.
	 */
	public function getOwner() {
		return $this->_owner;
	}

	/**
	 * Meta data model for the account billing details.
	 *
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing the meta data model.
	 */
	public function getBillingDetails() {
		return $this->_billing;
	}

	/**
	 * The API token used to identify an account creation.
	 * This token is platform specific and issued by Nosto.
	 *
	 * @return string the API token.
	 */
	public function getSignUpApiToken() {
		return $this->_sign_up_api_token;
	}
}
