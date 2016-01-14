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
 * Account component. Used as a helper to manage Nosto account inside Shopware.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Account extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * Creates a new Nosto account for the given shop.
	 *
	 * Note that the account is not saved anywhere and it is up to the caller to handle it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to create the account for.
	 * @param \Shopware\Models\Shop\Locale $locale the locale or null.
	 * @param stdClass|null $identity the user identity.
	 * @param string|null $email (optional) the account owner email if different than the active admin user.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the newly created account.
	 * @throws NostoException if the account cannot be created for any reason.
	 */
	public function createAccount(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null, $identity = null, $email = null)
	{
		$account = $this->findAccount($shop);
		if (!is_null($account)) {
			throw new NostoException(sprintf('Nosto account already exists for shop #%d.', $shop->getId()));
		}

		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account();
		$meta->loadData($shop, $locale, $identity);
		$validator = new Zend_Validate_EmailAddress();
		if ($validator->isValid($email)) {
			$meta->getOwner()->setEmail($email);
		}

		$service = new NostoServiceAccount();
		$nostoAccount = $service->create($meta);
		$account = $this->convertToShopwareAccount($nostoAccount, $shop);

		return $account;
	}

	/**
	 * Converts a `NostoAccount` into a `\Shopware\CustomModels\Nosto\Account\Account` model.
	 * If an existing SW account model exists for the shop, it will be updated and returned.
	 * If no existing account exists, one will be created and returned.
	 *
	 * NOTE: updating an account only updated the `data` field, not `id`, `shop_id` nor `name`.
	 *
	 * @param NostoAccount $nostoAccount the account to convert.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the account belongs to.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the account model.
	 */
	public function convertToShopwareAccount(\NostoAccount $nostoAccount, \Shopware\Models\Shop\Shop $shop)
	{
		$account = $this->findAccount($shop);
		if (is_null($account)) {
			$account = new \Shopware\CustomModels\Nosto\Account\Account();
			$account->setShopId($shop->getId());
			$account->setName($nostoAccount->getName());
		}
		$data = array('apiTokens' => array());
		foreach ($nostoAccount->getTokens() as $token) {
			$data['apiTokens'][$token->getName()] = $token->getValue();
		}
		$account->setData($data);
		return $account;
	}

	/**
	 * Converts a `\Shopware\CustomModels\Nosto\Account\Account` model into a `NostoAccount`.
	 *
	 * @param \Shopware\CustomModels\Nosto\Account\Account $account the account model.
	 * @return NostoAccount the nosto account.
	 */
	public function convertToNostoAccount(\Shopware\CustomModels\Nosto\Account\Account $account)
	{
		$nostoAccount = new NostoAccount($account->getName());
		foreach ($account->getData() as $key => $items) {
			if ($key === 'apiTokens') {
				foreach ($items as $name => $value) {
					$nostoAccount->addApiToken(new NostoApiToken($name, $value));
				}
			}
		}
		return $nostoAccount;
	}

	/**
	 * Removes the account and tells Nosto about it.
	 *
	 * @param \Shopware\CustomModels\Nosto\Account\Account $account the account to remove.
	 */
	public function removeAccount(\Shopware\CustomModels\Nosto\Account\Account $account)
	{
		$nostoAccount = $this->convertToNostoAccount($account);
		Shopware()->Models()->remove($account);
		Shopware()->Models()->flush();
		try {
			// Notify Nosto that the account was deleted.
			$service = new NostoServiceAccount();
			$service->delete($nostoAccount);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}
	}

	/**
	 * Finds a Nosto account for the given shop and returns it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to get the account for.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the account or null if not found.
	 */
	public function findAccount(\Shopware\Models\Shop\Shop $shop)
	{
		return Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Account\Account')
			->findOneBy(array('shopId' => $shop->getId()));
	}

	/**
	 * Checks if a Nosto account exists for a Shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to check the account for.
	 * @return bool true if account exists, false otherwise.
	 */
	public function accountExists(\Shopware\Models\Shop\Shop $shop)
	{
		$account = $this->findAccount($shop);
		return !is_null($account);
	}

	/**
	 * Builds the Nosto account administration iframe url and returns it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to get the url for.
	 * @param \Shopware\Models\Shop\Locale $locale the locale or null.
	 * @param \Shopware\CustomModels\Nosto\Account\Account|null $account the account to get the url for or null if account does not exist.
	 * @param stdClass|null $identity (optional) user identity.
	 * @param array $params (optional) parameters for the url.
	 * @return string the url.
	 */
	public function buildAccountIframeUrl(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null, \Shopware\CustomModels\Nosto\Account\Account $account = null, $identity = null, array $params = array())
	{
		$metaSso = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Sso();
		$metaSso->loadData($identity);
		$metaIframe = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe();
		$metaIframe->loadData($shop, $locale);

		if (!is_null($account)) {
			$nostoAccount = $this->convertToNostoAccount($account);
		} else {
			$nostoAccount = null;
		}

		return Nosto::helper('iframe')->getUrl($metaSso, $metaIframe, $nostoAccount, $params);
	}

	/**
	 * Sends a currency exchange rate update request to Nosto via the API.
	 *
	 * Checks if multi currency is enabled for the shop before attempting to
	 * send the exchange rates.
	 *
	 * @param \Shopware\CustomModels\Nosto\Account\Account $account the account for which to update the rates.
	 * @param \Shopware\Models\Shop\Shop $shop the shop which rates are to be updated.
	 *
	 * @return bool
	 */
	public function updateCurrencyExchangeRates(\Shopware\CustomModels\Nosto\Account\Account $account, \Shopware\Models\Shop\Shop $shop)
	{
		$currencies = $shop->getCurrencies();
		if (!($currencies->count() > 1)) {
			return false;
		}

		try {
			$currencyHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Currency();
			$collection = $currencyHelper->getShopExchangeRateCollection($shop);
			$service = new NostoServiceCurrencyExchangeRate($this->convertToNostoAccount($account));
			return $service->update($collection);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		return false;
	}

	/**
	 * Sends a update account request to Nosto via the API.
	 *
	 * This is used to update the details of a Nosto account from the
	 * "Advanced Settings" page, as well as after an account has been
	 * successfully connected through OAuth.
	 *
	 * @param \Shopware\CustomModels\Nosto\Account\Account $account the account to update.
	 * @param \Shopware\Models\Shop\Shop $shop the shop to which the account belongs.
	 *
	 * @return bool
	 */
	public function updateAccount(\Shopware\CustomModels\Nosto\Account\Account $account, \Shopware\Models\Shop\Shop $shop)
	{
		try {
			$identity = Shopware()->Auth()->getIdentity();
			$locale = (!is_null($identity) && isset($identity->locale)) ? $identity->locale : null;
			$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account();
			$meta->loadData($shop, $locale, $identity);
			$service = new NostoServiceAccount();
			return $service->update($this->convertToNostoAccount($account), $meta);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		return false;
	}
}
