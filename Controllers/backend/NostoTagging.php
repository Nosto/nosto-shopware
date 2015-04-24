<?php

class Shopware_Controllers_Backend_NostoTagging extends Shopware_Controllers_Backend_ExtJs {
	/**
	 * Loads the Nosto ExtJS sub-application for configuring Nosto for the shops.
	 * Default action.
	 */
	public function indexAction() {
		$this->View()->loadTemplate('backend/nosto_tagging/app.js');
	}

	/**
	 * Gets a list of accounts to populate client side Account models form.
	 * These are created for every shop, even if there is no Nosto account configured for it yet.
	 * This is done in order to streamline the functionality client side.
	 *
	 * This action should only be accessed by the Account store proxy in the client side application.
	 *
	 * The Account models are formatted as follows:
	 *
	 * [
	 *   {
	 *     'id'       => 1,                     // The Nosto account id if configured, null otherwise
	 *     'name'     => 'shopware-1234567',    // The Nosto account name if configured, null otherwise
	 *     'url'      => 'http://my.nosto.com', // The Nosto account admin url if configured, null otherwise
	 *     'shopId'   => 1,                     // Shopware Shop model id the Nosto account is connected to
	 *     'shopName' => 'English'              // Shopware Shop model name the Nosto account is connected to
	 *   },
	 *   ...
	 * ]
	 */
	public function getAccountsAction() {
		/** @var \Shopware\Models\Shop\Shop[] $result */
		$result = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findAll();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$data = array();
		foreach ($result as $shop) {
			$account = $helper->findAccount($shop);
			$account_data = array(
				'url' => $helper->buildAccountIframeUrl($shop, $account),
				'shopId' => $shop->getId(),
				'shopName' => $shop->getName(),
			);
			if (!is_null($account)) {
				$account_data['id'] = $account->getId();
				$account_data['name'] = $account->getName();
			}
			$data[] = $account_data;
		}

		$this->View()->assign(array('success' => true, 'data' => $data, 'total' => count($data)));
	}

	/**
	 * Ajax action for creating a new Nosto account and linking it to a Shop.
	 *
	 * This action should only be accessed by the Account model in the client side application.
	 */
	public function createAccountAction() {
		$shop_id = $this->Request()->getParam('shopId', null);
		$email = $this->Request()->getParam('email', null);

		/** @var \Shopware\Models\Shop\Shop $shop */
		$shop = Shopware()->Models()->find('\Shopware\Models\Shop\Shop', $shop_id);

		if (!is_null($shop)) {
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$account = $helper->createAccount($shop, $email);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);
				$data = array(
					'id' => $account->getId(),
					'name' => $account->getName(),
					'url' => $helper->buildAccountIframeUrl(
							$shop,
							$account,
							array(
								'message_type' => NostoMessage::TYPE_SUCCESS,
								'message_code' => NostoMessage::CODE_ACCOUNT_CREATE
							)
						),
					'shopId' => $shop->getId(),
					'shopName' => $shop->getName(),
				);
				$this->View()->assign(array('success' => true, 'data' => array($data)));
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->View()->assign(array('success' => false));
	}

	/**
	 * Ajax action for deleting a Nosto account for a Shop.
	 *
	 * This action should only be accessed by the Account model in the client side application.
	 */
	public function deleteAccountAction() {
		$account_id = $this->Request()->getParam('id', null);
		$shop_id = $this->Request()->getParam('shopId', null);

		/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
		$account = Shopware()->Models()->find('\Shopware\CustomModels\Nosto\Account\Account', $account_id);
		/** @var \Shopware\Models\Shop\Shop $shop */
		$shop = Shopware()->Models()->find('\Shopware\Models\Shop\Shop', $shop_id);

		if (!is_null($account) && !is_null($shop)) {
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$helper->removeAccount($account);
				$data = array(
					'url' => $helper->buildAccountIframeUrl(
							$shop,
							null,
							array(
								'message_type' => NostoMessage::TYPE_SUCCESS,
								'message_code' => NostoMessage::CODE_ACCOUNT_DELETE
							)
						),
					'shopId' => $shop->getId(),
					'shopName' => $shop->getName(),
				);
				$this->View()->assign(array('success' => true, 'data' => array($data)));
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->View()->assign(array('success' => false));
	}

	/**
	 * Ajax action for connecting an account via OAuth and linking it to a shop.
	 *
	 * This action should only be accessed by the Main controller in the client side application.
	 */
	public function connectAccountAction() {
		$shop_id = $this->Request()->getParam('shopId', null);

		/** @var \Shopware\Models\Shop\Shop $shop */
		$shop = Shopware()->Models()->find('\Shopware\Models\Shop\Shop', $shop_id);

		if (!is_null($shop)) {
			$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
			$meta->loadData($shop);
			$client = new NostoOAuthClient($meta);
			$data = array(
				'redirect_url' => $client->getAuthorizationUrl(),
			);

			$this->View()->assign(array('success' => true, 'data' => $data));
		} else {
			$this->View()->assign(array('success' => false));
		}
	}
}
