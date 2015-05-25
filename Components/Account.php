<?php

/**
 * Account component. Used as a helper to manage Nosto account inside Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Account
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

		$nostoAccount = NostoAccount::create($meta);
		$account = $this->convertToShopwareAccount($nostoAccount, $shop);

		return $account;
	}

	/**
	 * Converts a `NostoAccount` into a `\Shopware\CustomModels\Nosto\Account\Account` model.
	 *
	 * @param NostoAccount $nostoAccount the account to convert.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the account belongs to.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the account model.
	 */
	public function convertToShopwareAccount(\NostoAccount $nostoAccount, \Shopware\Models\Shop\Shop $shop)
	{
		$account = new \Shopware\CustomModels\Nosto\Account\Account();
		$account->setShopId($shop->getId());
		$account->setName($nostoAccount->getName());
		$data = array('apiTokens' => array());
		foreach ($nostoAccount->tokens as $token) {
			$data['apiTokens'][$token->name] = $token->value;
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
		$nostoAccount = new NostoAccount();
		$nostoAccount->name = $account->getName();
		foreach ($account->getData() as $key => $items) {
			if ($key === 'apiTokens') {
				foreach ($items as $token_name => $token_value) {
					$token = new NostoApiToken();
					$token->name = $token_name;
					$token->value = $token_value;
					$nostoAccount->tokens[] = $token;
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
			$nostoAccount->delete();
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
	 * Checks if a Nosto account exists for a Shop and that it is connected to Nosto.
	 *
	 * Connected here means that we have the API tokens exchanged during account creation or OAuth.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to check the account for.
	 * @return bool true if account exists and is connected to Nosto, false otherwise.
	 */
	public function accountExistsAndIsConnected(\Shopware\Models\Shop\Shop $shop)
	{
		$account = $this->findAccount($shop);
		if (is_null($account)) {
			return false;
		}
		$nostoAccount = $this->convertToNostoAccount($account);
		return $nostoAccount->isConnectedToNosto();
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
		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe();
		$meta->loadData($shop, $locale, $identity);
		if (!is_null($account)) {
			$nostoAccount = $this->convertToNostoAccount($account);
		} else {
			$nostoAccount = null;
		}
		return Nosto::helper('iframe')->getUrl($meta, $nostoAccount, $params);
	}
}
