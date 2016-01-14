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
 * Main frontend controller. Handles account connection via OAuth 2 and data
 * exports for products and orders.
 *
 * Extends Enlight_Controller_Action.
 *
 * @package Shopware
 * @subpackage Controllers_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Controllers_Frontend_NostoTagging extends Enlight_Controller_Action
{
	/**
	 * Handles the redirect from Nosto oauth2 authorization server when an existing account is connected to a shop.
	 * This is handled in the front end as the oauth2 server validates the "return_url" sent in the first step of the
	 * authorization cycle, and requires it to be from the same domain that the account is configured for and only
	 * redirects to that domain.
	 */
	public function oauthAction()
	{
		$shop = Shopware()->Shop();
		$code = $this->Request()->getParam('code');
		$error = $this->Request()->getParam('error');

		if (!is_null($code)) {
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$oldAccount = $helper->findAccount($shop);
				if (!is_null($oldAccount)) {
					$oldNostoAccount = $helper->convertToNostoAccount($oldAccount);
				} else {
					$oldNostoAccount = null;
				}

				$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
				$meta->loadData($shop, null, $oldNostoAccount);

				$service = new NostoServiceAccount();
				$newNostoAccount = $service->sync($meta, $code);

				// If we are updating an existing account, double check that we
				// got the same account back from Nosto.
				if (!is_null($oldNostoAccount) && !$newNostoAccount->equals($oldNostoAccount)) {
					throw new NostoException('Failed to sync account details, account mismatch.');
				}

				$account = $helper->convertToShopwareAccount($newNostoAccount, $shop);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);
				$helper->updateCurrencyExchangeRates($account, $shop);
				$helper->updateAccount($account, $shop);

				$redirectParams = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
					'openNosto' => $shop->getId(),
					'messageType' => NostoMessage::TYPE_SUCCESS,
					'messageCode' => NostoMessage::CODE_ACCOUNT_CONNECT,
				);
				$this->redirect($redirectParams, array('code' => 302));
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);

				$redirectParams = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
					'openNosto' => $shop->getId(),
					'messageType' => NostoMessage::TYPE_ERROR,
					'messageCode' => NostoMessage::CODE_ACCOUNT_CONNECT,
				);
				$this->redirect($redirectParams, array('code' => 302));
			}
		} elseif (!is_null($error)) {
			$errorReason = $this->Request()->getParam('error_reason');
			$errorDescription = $this->Request()->getParam('error_description');

			$logMessage = $error;
			if (!is_null($errorReason)) {
				$logMessage .= ' - '.$errorReason;
			}
			if (!is_null($errorDescription)) {
				$logMessage .= ' - '.$errorDescription;
			}

			Shopware()->Pluginlogger()->error($logMessage);

			$redirectParams = array(
				'module' => 'backend',
				'controller' => 'index',
				'action' => 'index',
				'openNosto' => $shop->getId(),
				'messageType' => NostoMessage::TYPE_ERROR,
				'messageCode' => NostoMessage::CODE_ACCOUNT_CONNECT,
				'messageText' => $errorDescription,
			);
			$this->redirect($redirectParams, array('code' => 302));
		} else {
			throw new Zend_Controller_Action_Exception('Not Found', 404);
		}
	}

	/**
	 * Exports products from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportProductsAction()
	{
		$pageSize = (int)$this->Request()->getParam('limit', 100);
		$currentOffset = (int)$this->Request()->getParam('offset', 0);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('articles.id'))
			->from('\Shopware\Models\Article\Article', 'articles')
			->where('articles.active = 1')
			->setFirstResult($currentOffset)
			->setMaxResults($pageSize)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$collection = new NostoExportCollectionProduct();
		foreach ($result as $row) {
			/** @var Shopware\Models\Article\Article $article */
			$article = Shopware()->Models()->find('Shopware\Models\Article\Article', (int)$row['id']);
			if (is_null($article)) {
				continue;
			}
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			try {
				$model->loadData($article);
				$collection[] = $model;
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->export($collection);
	}

	/**
	 * Exports completed orders from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportOrdersAction()
	{
		$pageSize = (int)$this->Request()->getParam('limit', 100);
		$currentOffset = (int)$this->Request()->getParam('offset', 0);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('orders.number'))
			->from('\Shopware\Models\Order\Order', 'orders')
			->where('orders.status >= 0')
			->setFirstResult($currentOffset)
			->setMaxResults($pageSize)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$collection = new NostoExportCollectionOrder();
		foreach ($result as $row) {
			/** @var Shopware\Models\Order\Order $order */
			$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
				->findOneBy(array('number' => $row['number']));
			if (is_null($order)) {
				continue;
			}
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
			$model->disableSpecialLineItems();
			try {
				$model->loadData($order);
				$collection[] = $model;
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->export($collection);
	}

	/**
	 * Encrypts the export collection and outputs it to the browser.
	 *
	 * @param NostoExportCollectionInterface $collection the data collection to export.
	 */
	protected function export(NostoExportCollectionInterface $collection)
	{
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $helper->findAccount($shop);
		if (!is_null($account)) {
			$cipherText = NostoExporter::export($helper->convertToNostoAccount($account), $collection);
			echo $cipherText;
		}
		die();
	}
}
