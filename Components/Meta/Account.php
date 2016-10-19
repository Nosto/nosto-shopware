<?php
/**
 * Copyright (c) 2016, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Meta-data class for account information sent to Nosto during account create.
 *
 * Implements NostoAccountMetaDataInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
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
	 * @var array|stdClass the account details
	 */
	protected $_details;

	/**
	 * Loads the meta data for the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to load the data for.
	 * @param \Shopware\Models\Shop\Locale $locale the locale or null.
	 * @param stdClass|null $identity the user identity.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null, $identity = null)
	{
		if (is_null($locale)) {
			$locale = $shop->getLocale();
		}

		$this->_title = Shopware()->App().' - '.$shop->getName();
		$this->_name = substr(sha1(rand()), 0, 8);
		$this->_frontPageUrl = $this->buildStoreUrl($shop);
		$this->_currencyCode = strtoupper($shop->getCurrency()->getCurrency());
		$this->_languageCode = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->_ownerLanguageCode = strtolower(substr($locale->getLocale(), 0, 2));

		$this->_owner = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner();
		$this->_owner->loadData($identity);

		$this->_billing = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing();
		$this->_billing->loadData($shop);
	}

	/**
	 * Adds required store selection params to the url.
	 *
	 * These params is `__shop`
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url with added params.
	 */
	protected function buildStoreUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array('module' => 'frontend'));
		$defaults = array(
			'__shop' => $shop->getId()
		);
		return NostoHttpRequest::replaceQueryParamsInUrl($defaults, $url);
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

	/**
	 * Optional partner code for Nosto partners.
	 * The code is issued by Nosto to partners only.
	 *
	 * @return string|null the partner code or null if none exist.
	 */
	public function getPartnerCode()
	{
		return null;
	}

	/**
	 * @return array
	 */
	public function getCurrencies()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function getUseCurrencyExchangeRates()
	{
		return false;
	}

	/**
	 * @return null
	 */
	public function getDefaultVariationId()
	{
		return null;
	}

	/**
	 * Returns the account details
	 *
	 * @return array|stdClass
	 */
	public function getDetails()
	{
		return $this->_details;
	}

	/**
	 * Sets the account details
	 *
	 * @param array|stdClass $details
	 */
	public function setDetails($details)
	{
		$this->_details = $details;
	}



}
