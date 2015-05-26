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
 * Product operation component. Used for communicating create/update/delete
 * events for products to Nosto.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product
{
	/**
	 * Sends info to Nosto about a newly created product.
	 *
	 * @param \Shopware\Models\Article\Article $article the product.
	 */
	public function create(\Shopware\Models\Article\Article $article)
	{
		$validator = new NostoModelValidator();
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts() as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($article, $shop);
			if (!$validator->validate($model)) {
				continue;
			}
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->create();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * Sends info to Nosto about a newly updated product.
	 *
	 * @param \Shopware\Models\Article\Article $article the product.
	 */
	public function update(\Shopware\Models\Article\Article $article)
	{
		$validator = new NostoModelValidator();
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts() as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($article, $shop);
			if (!$validator->validate($model)) {
				continue;
			}
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->update();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * Sends info to Nosto about a deleted product.
	 *
	 * @param \Shopware\Models\Article\Article $article the product.
	 */
	public function delete(\Shopware\Models\Article\Article $article)
	{
		foreach ($this->getAccounts() as $account) {
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->assignId($article);
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->delete();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * Returns the available Nosto accounts mapped on the shop ID to which they
	 * belong.
	 *
	 * @return NostoAccount[] the accounts mapped in the shop IDs.
	 */
	protected function getAccounts()
	{
		// todo: this is all account, can the product be saved/deleted for just one shop?
		$data = array();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$accounts = Shopware()->Models()->getRepository('\Shopware\CustomModels\Nosto\Account\Account')->findAll();
		foreach ($accounts as $account) {
			$nostoAccount = $helper->convertToNostoAccount($account);
			if ($nostoAccount->isConnectedToNosto()) {
				$data[$account->getShopId()] = $nostoAccount;
			}
		}
		return $data;
	}
}
