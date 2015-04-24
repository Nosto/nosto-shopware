<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Account {
	/**
	 * Creates a new Nosto account for the given shop.
	 *
	 * Note that the account is not saved anywhere and it is up to the caller to handle it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to create the account for.
	 * @param string|null $email (optional) the account owner email if different than the active admin user.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the newly created account.
	 * @throws NostoException if the account cannot be created for any reason.
	 */
	public function createAccount(\Shopware\Models\Shop\Shop $shop, $email = null) {
		$account = $this->findAccount($shop);
		if (!is_null($account)) {
			throw new NostoException(sprintf('Nosto account already exists for shop #%d.', $shop->getId()));
		}

		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account();
		$meta->loadData($shop);
		$validator = new Zend_Validate_EmailAddress();
		if ($validator->isValid($email)) {
			$meta->getOwner()->setEmail($email);
		}

		$nosto_account = NostoAccount::create($meta);

		$account = new \Shopware\CustomModels\Nosto\Account\Account();
		$account->setShopId($shop->getId());
		$account->setName($nosto_account->getName());
		$data = array('apiTokens' => array());
		foreach ($nosto_account->tokens as $token) {
			$data['apiTokens'][$token->name] = $token->value;
		}
		$account->setData($data);

		// todo: validate model.

		return $account;
	}

	/**
	 * Removes the account and tells Nosto about it.
	 *
	 * @param \Shopware\CustomModels\Nosto\Account\Account $account the account to remove.
	 */
	public function removeAccount(\Shopware\CustomModels\Nosto\Account\Account $account) {
		$nosto_account = $account->toNostoAccount();
		Shopware()->Models()->remove($account);
		Shopware()->Models()->flush();
		// Notify Nosto that the account was deleted.
		$nosto_account->delete();
	}

	/**
	 * Finds a Nosto account for the given shop and returns it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to get the account for.
	 * @return \Shopware\CustomModels\Nosto\Account\Account the account or null if not found.
	 */
	public function findAccount(\Shopware\Models\Shop\Shop $shop) {
		return Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Account\Account')
			->findOneBy(array('shop_id' => $shop->getId()));
	}

	/**
	 * Builds the Nosto account administration iframe url and returns it.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop to get the url for.
	 * @param \Shopware\CustomModels\Nosto\Account\Account|null $account the account to get the url for or null if account does not exist.
	 * @param array $params (optional) parameters for the url.
	 * @return string the url.
	 */
	public function buildAccountIframeUrl(\Shopware\Models\Shop\Shop $shop, \Shopware\CustomModels\Nosto\Account\Account $account = null, array $params = array()) {
		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe();
		$meta->loadData($shop);
		if (!is_null($account)) {
			$nosto_account = $account->toNostoAccount();
		} else {
			$nosto_account = null;
		}
		return Nosto::helper('iframe')->getUrl($meta, $nosto_account, $params);
	}
}
