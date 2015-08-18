<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Meta-data class for account information sent to Nosto during account create.
 *
 * Implements NostoAccountMetaInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account implements NostoAccountMetaInterface
{
	/**
	 * @var string the store name.
	 */
	protected $title;

	/**
	 * @var string the account name.
	 */
	protected $name;

	/**
	 * @var string the store front end url.
	 */
	protected $frontPageUrl;

	/**
	 * @var NostoCurrencyCode the store currency ISO (ISO 4217) code.
	 */
	protected $currency;

	/**
	 * @var NostoLanguageCode the store language ISO (ISO 639-1) code.
	 */
	protected $language;

	/**
	 * @var NostoLanguageCode the owner language ISO (ISO 639-1) code.
	 */
	protected $ownerLanguage;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner the account owner meta model.
	 */
	protected $owner;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing the billing meta model.
	 */
	protected $billing;

	/**
	 * @var string the API token used to identify an account creation.
	 */
	protected $signUpApiToken = 'kIqtTZOTRTNJ1zPZgjkI4Ft572sfLrqjD4XewXqYrdGrqsgnYbWqGXR3Evxqmii1';

	/**
	 * Loads the Data Transfer Object.
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

		$this->title = Shopware()->App().' - '.$shop->getName();
		$this->name = substr(sha1(rand()), 0, 8);
		$this->frontPageUrl = Shopware()->Front()->Router()->assemble(array('module' => 'frontend'));
		$this->currency = new NostoCurrencyCode($shop->getCurrency()->getCurrency());
		$this->language = new NostoLanguageCode(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->ownerLanguage = new NostoLanguageCode(substr($locale->getLocale(), 0, 2));

		$this->owner = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner();
		$this->owner->loadData($identity);

		$this->billing = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing();
		$this->billing->loadData($shop);
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	public function getPlatform()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * @inheritdoc
	 */
	public function getFrontPageUrl()
	{
		return $this->frontPageUrl;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @inheritdoc
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @inheritdoc
	 */
	public function getOwnerLanguage()
	{
		return $this->ownerLanguage;
	}

	/**
	 * @inheritdoc
	 */
	public function getOwner()
	{
		return $this->owner;
	}

	/**
	 * @inheritdoc
	 */
	public function getBillingDetails()
	{
		return $this->billing;
	}

	/**
	 * @inheritdoc
	 */
	public function getSignUpApiToken()
	{
		return $this->signUpApiToken;
	}

	/**
	 * @inheritdoc
	 */
	public function getPartnerCode()
	{
		// todo: implement storage for partner code
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrencies()
	{
		// todo: implement for multi-currency
		return array();
	}

	/**
	 * @inheritdoc
	 */
	public function getDefaultPriceVariationId()
	{
		// todo: implement for multi-currency
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getUseCurrencyExchangeRates()
	{
		// todo: implement for multi-currency
		return array();
	}
}
