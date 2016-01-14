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
 * Product update service. Used for communicating create/update/delete
 * events for products to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Service_Product extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * Sends info to Nosto about a newly created product.
	 *
	 * @param \Shopware\Models\Article\Article $article the product.
	 */
	public function create(\Shopware\Models\Article\Article $article)
	{
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts($article) as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			try {
				$model->loadData($article, $shop);
				$service = new NostoServiceProduct($account);
				$service->addProduct($model);
				$service->create();
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
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts($article) as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			try {
				$model->loadData($article, $shop);
				$service = new NostoServiceProduct($account);
				$service->addProduct($model);
				$service->update();
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
		foreach ($this->getAccounts($article) as $account) {
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->setProductId((int)$article->getId());
			try {
				$service = new NostoServiceProduct($account);
				$service->addProduct($model);
				$service->delete();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * Returns the Nosto accounts for the product mapped on the shop ID to which
	 * they belong.
	 *
	 * The shops the product belongs to is determined by analyzing the products
	 * categories and checking if the shop root category is present.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @return NostoAccount[] the accounts mapped in the shop IDs.
	 */
	protected function getAccounts(\Shopware\Models\Article\Article $article)
	{
		$data = array();

		/** @var \Shopware\Models\Shop\Shop[] $inShops */
		$inShops = array();
		$allShops = Shopware()
			->Models()
			->getRepository('\Shopware\Models\Shop\Shop')
			->findAll();
		/** @var Shopware\Models\Category\Category $cat */
		foreach ($article->getCategories() as $cat) {
			foreach ($allShops as $shop) {
				if (isset($inShops[$shop->getId()])) {
					continue;
				}
				$shopCatId = $shop->getCategory()->getId();
				if ($cat->getId() === $shopCatId
					|| strpos($cat->getPath(), '|'.$shopCatId.'|') !== false) {
					$inShops[$shop->getId()] = $shop;
				}
			}
		}

		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		foreach ($inShops as $shop) {
			$account = $helper->findAccount($shop);
			if (!is_null($account)) {
				$nostoAccount = $helper->convertToNostoAccount($account);
				$data[$shop->getId()] = $nostoAccount;
			}
		}

		return $data;
	}
}
