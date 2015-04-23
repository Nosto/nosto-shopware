<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Account {
	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @param null $email
	 * @return \Shopware\Plugins\Frontend\NostoTagging\Models\Account
	 * @throws NostoException
	 */
	public function createAccount(\Shopware\Models\Shop\Shop $shop, $email = null) {
		$account = $this->findAccount($shop);
		if (!is_null($account)) {
			throw new NostoException(sprintf('Nosto account already exists for shop #%d.', $shop->getId()));
		}

		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account();
		$meta->loadData($shop);
		//$meta->getOwner()->setEmail($email); // todo: validate email

		$nosto_account = NostoAccount::create($meta);

		$account = new \Shopware\Plugins\Frontend\NostoTagging\Models\Account();
		$account->setShopId($shop->getId());
		$account->setName($nosto_account->getName());
		$data = array('apiTokens' => array());
		foreach ($nosto_account->tokens as $token) {
			$data['apiTokens'][$token->name] = $token->value;
		}
		$account->setData($data);

		return $account;
	}

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @return \Shopware\Plugins\Frontend\NostoTagging\Models\Account
	 */
	public function findAccount(\Shopware\Models\Shop\Shop $shop) {
		// todo: implement
		/** @var \Shopware\Plugins\Frontend\NostoTagging\Models\Account $account */
		/*$account = Shopware()
			->Models()
			->getRepository('\Shopware\Plugins\Frontend\NostoTagging\Models\Account')
			->findOneBy(array('shop_id' => $shop->getId()));*/
		//var_dump($account);die;
		return null;
	}

	/**
	 * @param \Shopware\Plugins\Frontend\NostoTagging\Models\Account|null $account
	 * @param array $params
	 * @return string
	 */
	public function buildAccountIframeUrl(\Shopware\Plugins\Frontend\NostoTagging\Models\Account $account = null, array $params = array()) {
		// todo: implement
//		$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Account_Iframe();
//		$meta->loadData($account);
//		return Nosto::helper('iframe')->getUrl($meta, $account->convertIntoNostoAccount(), $params);

		return 'https://staging.nosto.com/hub/magento/install?email=christoffer.lindqvist@nosto.com';
	}
}
