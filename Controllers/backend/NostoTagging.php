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
 * Main backend controller. Handles account create/connect/delete requests
 * from the account configuration iframe.
 *
 * Extends Shopware_Controllers_Backend_ExtJs.
 *
 * @package Shopware
 * @subpackage Controllers_Backend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Controllers_Backend_NostoTagging extends Shopware_Controllers_Backend_ExtJs
{
	const DEFAULT_IFRAME_ORIGIN_REGEXP = '(https:\/\/shopware-([a-z0-9]+)\.hub\.nosto\.com)|(https:\/\/my\.nosto\.com)';

	/**
	 * Loads the Nosto ExtJS sub-application for configuring Nosto for the shops.
	 * Default action.
	 */
	public function indexAction()
	{
		$this->View()->loadTemplate('backend/nosto_tagging/app.js');
	}

	/**
	 * Ajax action for getting all ExtJs stores in the client side app.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function loadStoresAction()
	{
		$this->View()->assign(array(
			'success' => true,
			'data' => array(
				'accounts' => $this->getAccountStoreData(),
				'configs' => $this->getConfigStoreData(),
				'settings' => $this->getSettingStoreData(),
				'multiCurrencyMethods' => $this->getMultiCurrencyStoreData(),
			)
		));
	}

	/**
	 * Ajax action for creating a new Nosto account and linking it to a Shop.
	 *
	 * This action should only be accessed by the Account model in the client
	 * side application.
	 */
	public function createAccountAction()
	{
		$success = false;
		$data = array();
		$shopId = $this->Request()->getParam('shopId', null);
		$email = $this->Request()->getParam('email', null);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$identity = Shopware()->Auth()->getIdentity();

		if (!is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$locale = (!is_null($identity) && isset($identity->locale)) ? $identity->locale : null;
				$account = $helper->createAccount($shop, $locale, $identity, $email);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);
				$helper->updateCurrencyExchangeRates($account, $shop);
				$success = true;
				$data = array(
					'id' => $account->getId(),
					'name' => $account->getName(),
					'url' => $helper->buildAccountIframeUrl(
							$shop,
							$identity->locale,
							$account,
							$identity,
							array(
								'message_type' => NostoMessage::TYPE_SUCCESS,
								'message_code' => NostoMessage::CODE_ACCOUNT_CREATE
							)
						),
					'shopId' => $shop->getId(),
					'shopName' => $shop->getName(),
				);
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for deleting a Nosto account for a Shop.
	 *
	 * This action should only be accessed by the Account model in the client
	 * side application.
	 */
	public function deleteAccountAction()
	{
		$success = false;
		$data = array();
		$accountId = $this->Request()->getParam('id', null);
		$shopId = $this->Request()->getParam('shopId', null);
		/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
		$account = Shopware()->Models()->find('\Shopware\CustomModels\Nosto\Account\Account', $accountId);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$identity = Shopware()->Auth()->getIdentity();

		if (!is_null($account) && !is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
			$helper->removeAccount($account);
			$success = true;
			$data = array(
				'url' => $helper->buildAccountIframeUrl(
						$shop,
						$identity->locale,
						null,
						$identity,
						array(
							'message_type' => NostoMessage::TYPE_SUCCESS,
							'message_code' => NostoMessage::CODE_ACCOUNT_DELETE
						)
					),
				'shopId' => $shop->getId(),
				'shopName' => $shop->getName(),
			);
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for connecting an account via OAuth and linking it to a shop.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function connectAccountAction()
	{
		$success = false;
		$data = array();
		$shopId = $this->Request()->getParam('shopId', null);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$locale = Shopware()->Auth()->getIdentity()->locale;

		if (!is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
			$meta->loadData($shop, $locale);
			$client = new NostoOAuthClient($meta);
			$success = true;
			$data = array('redirect_url' => $client->getAuthorizationUrl());
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for synchronising an account via OAuth and linking it to a shop.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function syncAccountAction()
	{
		$success = false;
		$data = array();
		$shopId = $this->Request()->getParam('shopId', null);

		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();

		$shop = $repository->getActiveById($shopId);
		$locale = Shopware()->Auth()->getIdentity()->locale;

		if (!is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			$account = $helper->findAccount($shop);
			if (!is_null($account)) {
				$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
				$meta->loadData($shop, $locale, $helper->convertToNostoAccount($account));
				$client = new NostoOAuthClient($meta);
				$success = true;
				$data = array('redirect_url' => $client->getAuthorizationUrl());
			}
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for updating Nosto accounts.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function updateAccountsAction()
	{
		$response = array(
			'messages' => array()
		);

		/* @var \Shopware\CustomModels\Nosto\Account\Repository $accountRepository */
		$accountRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Nosto\Account\Account');
		/* @var \Shopware\Models\Shop\Repository $shopRepository */
		$shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();

		foreach ($accountRepository->findAll() as $account) {
			/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
			$shop = $shopRepository->getActiveById($account->getShopId());
			$shop->registerResources(Shopware()->Bootstrap());
			if ($helper->updateAccount($account, $shop)) {
				$response['messages'][] = array(
					'title' => 'Success',
					'text' => sprintf("The account has been updated for the %s shop.", $shop->getName())
				);
			} else {
				$response['messages'][] = array(
					'title' => 'Error',
					'text' => sprintf("There was an error updating the account for the %s shop. More information can be found in the system's plugin error log.", $shop->getName())
				);
			}
		}

		if (empty($response['messages'])) {
			$response['messages'][] = array(
				'title' => 'Error',
				'text' => "Nosto has not been installed in any shop. Please make sure you have installed Nosto to at least one of your shops."
			);
		}

		$this->View()->assign(array('success' => true, 'data' => $response));
	}

	/**
	 * Ajax action for updating currency exchange rates for Nosto accounts.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function updateCurrencyExchangeRatesAction()
	{
		$response = array(
			'messages' => array()
		);

		/* @var \Shopware\CustomModels\Nosto\Account\Repository $accountRepository */
		$accountRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Nosto\Account\Account');
		/* @var \Shopware\Models\Shop\Repository $shopRepository */
		$shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();

		foreach ($accountRepository->findAll() as $account) {
			/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
			$shop = $shopRepository->getActiveById($account->getShopId());
			$shop->registerResources(Shopware()->Bootstrap());
			if ($helper->updateCurrencyExchangeRates($account, $shop)) {
				$response['messages'][] = array(
					'title' => 'Success',
					'text' => sprintf("The exchange rates have been updated for the %s shop.", $shop->getName())
				);
			} else {
				$response['messages'][] = array(
					'title' => 'Error',
					'text' => sprintf("There was an error updating the exchange rates for the %s shop. More information can be found in the system's plugin error log.", $shop->getName())
				);
			}
		}

		if (empty($response['messages'])) {
			$response['messages'][] = array(
				'title' => 'Error',
				'text' => "Nosto has not been installed in any shop. Please make sure you have installed Nosto to at least one of your shops."
			);
		}

		$this->View()->assign(array('success' => true, 'data' => $response));
	}

	/**
	 * Ajax action for updating the "Advanced Settings".
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function saveAdvancedSettingsAction()
	{
		$response = array(
			'success' => true,
			'data' => array('messages' => array())
		);

		$attributes = array('multiCurrencyMethod');
		$persistedSettings = array();
		foreach ($attributes as $attributeName) {
			$attributeValue = $this->Request()->getParam($attributeName, null);
			if (!is_null($attributeValue)) {
				$setting = Shopware()
					->Models()
					->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
					->findOneBy(array('name' => $attributeName));
				if (is_null($setting)) {
					$setting = new \Shopware\CustomModels\Nosto\Setting\Setting();
					$setting->setName($attributeName);
				}
				$setting->setValue($attributeValue);
				$violations = Shopware()->Models()->validate($setting);
				if ($violations->count() > 0) {
					$response['success'] = false;
					foreach ($violations as $violation) {
						Shopware()->Pluginlogger()->error($violation);
					}
				}
				Shopware()->Models()->persist($setting);
				$persistedSettings[] = $setting;
			}
		}

		if (count($persistedSettings) > 0) {
			Shopware()->Models()->flush($persistedSettings);
		}

		if ($response['success']) {
			$response['data']['messages'][] = array(
				'title' => 'Success',
				'text' => 'Settings have been saved.',
			);
		} else {
			$response['data']['messages'][] = array(
				'title' => 'Error',
				'text' => 'Settings have NOT been saved. See system plugin error log for more information.',
			);
		}

		$this->View()->assign($response);
	}

	/**
	 * Gets a list of accounts to populate client side Account models.
	 * These are created for every shop, even if there is no Nosto account
	 * configured for it yet. This is done in order to streamline the
	 * functionality client side.
	 *
	 * This action should only be accessed by the Account store proxy in the
	 * client side application.
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
	 *
	 * @return array the data
	 */
	protected function getAccountStoreData()
	{
		$data = array();

		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		if (method_exists($repository, 'getActiveShops')) {
			$result = $repository->getActiveShops(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		} else {
			// SW 4.0 does not have the `getActiveShops` method, so we fall back
			// on manually building the query.
			$result = $repository->createQueryBuilder('shop')
				->where('shop.active = 1')
				->getQuery()
				->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		}
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$identity = Shopware()->Auth()->getIdentity();
		$setting = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findOneBy(array('name' => 'oauthParams'));
		if (!is_null($setting)) {
			$oauthParams = json_decode($setting->getValue(), true);
			Shopware()->Models()->remove($setting);
			Shopware()->Models()->flush();
		}

		foreach ($result as $row) {
			$params = array();
			$shop = $repository->getActiveById($row['id']);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$account = $helper->findAccount($shop);
			if (isset($oauthParams[$shop->getId()])) {
				$params = $oauthParams[$shop->getId()];
			}
			$accountData = array(
				'url' => $helper->buildAccountIframeUrl($shop, $identity->locale, $account, $identity, $params),
				'shopId' => $shop->getId(),
				'shopName' => $shop->getName(),
			);
			if (!is_null($account)) {
				$accountData['id'] = $account->getId();
				$accountData['name'] = $account->getName();
			}
			$data[] = $accountData;
		}

		return $data;
	}

	/**
	 * Gets a list of configs to populate client side Config models.
	 * There will always be only one.
	 *
	 * The Config models are formatted as follows:
	 *
	 * array(
	 *   array(
	 *     'postMessageOrigin' => '.*', // The iframe origin regexp for validating the window.postMessage() events
	 *   )
	 * )
	 *
	 * @return array the data
	 */
	protected function getConfigStoreData()
	{
		return array(
			array(
				'postMessageOrigin' => Nosto::getEnvVariable('NOSTO_IFRAME_ORIGIN_REGEXP', self::DEFAULT_IFRAME_ORIGIN_REGEXP)
			)
		);
	}

	/**
	 * Gets a list of settings to populate client side Setting models.
	 * There will always be only one.
	 *
	 * The Setting models are formatted as follows:
	 *
	 * array(
	 *   array(
	 *     'multiCurrencyMethod' => 'exchangeRate', // The currency multi currency method
	 *   )
	 * )
	 *
	 * @return array the data
	 */
	protected function getSettingStoreData()
	{
		$settings = array(
			'multiCurrencyMethod' => 'exchangeRate'
		);

		$models = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findAll();

		foreach ($models as $model) {
			$settings[$model->getName()] = $model->getValue();
		}

		return array($settings);
	}

	/**
	 * Gets a list of multi currency method options to populate client side MultiCurrencyMethod models.
	 *
	 * The MultiCurrencyMethod models are formatted as follows:
	 *
	 * array(
	 *   array(
	 *     'id' => 'exchangeRate', // The currency multi currency method id
	 *     'name' => 'Exchange Rate', // The currency multi currency method name
	 *   ),
	 *   ...
	 * )
	 *
	 * @return array the data
	 */
	protected function getMultiCurrencyStoreData()
	{
		return array(
			array(
				'id' => Shopware_Plugins_Frontend_NostoTagging_Bootstrap::MULTI_CURRENCY_METHOD_EXCHANGE_RATE,
				'name' => 'Exchange Rate'
			),
			array(
				'id' => Shopware_Plugins_Frontend_NostoTagging_Bootstrap::MULTI_CURRENCY_METHOD_PRICE_VARIATION,
				'name' => 'Product Variation'
			)
		);
	}
}
